<?php

namespace App\Services\Api;

use App\Models\Cart;
use App\Models\Rate;
use App\Models\Slot;
use App\Models\Session;
use App\Models\GiftCard;
use App\Models\Inscription;
use App\Models\SessionSlot;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\AssignatedRate;
use App\Models\SessionTempSlot;

/**
 * Encapsulates API functions applied on Order.
 *
 * This comes from OrderApiController. We have a generic route
 * /v1/order/$id/foo-bar which controller will call the method
 * setFooBar of this class
 *
 * @author miquel
 */
class InscriptionService extends AbstractService
{

    /** @var Cart */
    private $cart;

    /** @var Session */
    private $session;

    /** @var \Illuminate\Support\Collection */
    private $inscriptions;

    /** @var \Illuminate\Support\Collection */
    private $slots_to_block;

    /** @var boolean */
    private $is_for_a_pack;

    public function __construct()
    {
        $this->clear();
    }

    public function clear()
    {
        $this->inscriptions = collect();
        $this->slots_to_block = collect();
    }

    /**
     * Creates a set of Inscriptions and add them to the Cart.
     *
     * Returns a Collection of the created Inscriptions
     *
     * @param array $params needs:
     * All field are mandatory, Controller must ensure to call Service correctly.
     * Example of params:
     *  {
     *      "cart_id": 1,
     *      "session_id": 2,
     *      "inscriptions": [
     *          {
     *            "numbered":false,
     *            "rate_id": 1,
     *            "quantity": 2 (only for unumbered)
     *            "slot_id": 1 (only for numbered)
     *            "codes": [415754,451214] (only for unumbered)
     *          }
     *      ]
     *  }
     * @return \Illuminate\Support\Collection
     */
    public function createNewInscriptionsSet(array $params, $isForAPack = false)
    {
        $this->initAttributes($params['cart_id'], $params['session_id'], $isForAPack);

        // TODO: at what moment do we check ownership constraints?
        \DB::transaction(function () use ($params) {
            $this->createInscriptions($params['inscriptions'], $params);
        }, 2);

        return $this->inscriptions;
    }

    /**
     * Removes already carted Inscriptions that belongs to the same inscriptions
     * contained in $params.
     *
     * This is called before calling createNewInscriptionsSet to ensure we do not
     * add twice tickets for the same session
     *
     * @param array $params
     */
    public function removeOldInscriptionsSet(array $params)
    {
        $this->initAttributes($params['cart_id'], $params['session_id']);

        // if Cart already contains some Inscription of this Session,
        // we dettach them. In order to avoid overbooking
        $this->cart->inscriptions()->where('group_pack_id', NULL)->where('session_id', $this->session->id)->delete();
    }

    public function createNewInscriptionsBySlot(array $params)
    {
        $this->initAttributes($params['cart_id'], $params['session_id']);

        $params['numbered'] = true; // just in case
        \DB::transaction(function () use ($params) {
            $this->createInscriptions($params['inscriptions'], $params);
        }, 2);
    }

    /**
     * Return current cart
     *
     * @return Cart
     */
    public function getCart()
    {
        return $this->cart;
    }

    private function initAttributes($cart_id, $session_id, $isForAPack = false)
    {
        $this->is_for_a_pack = $isForAPack;
        $this->slots_to_block = collect();
        if (!isset($this->cart)) {
            $this->cart = Cart::query()->findOrFail($cart_id);
        }

        // if service is using from backend, expired sessions can be sold
        if ($this->private_usage) {
            $this->session = Session::query()
                ->where('id', $session_id)->firstOrFail();
        } else if ($isForAPack) {
            // packs don't use inscription_starts_on from session
            $this->session = Session::query()
                ->where('ends_on', '>', \Carbon\Carbon::now())
                ->where('id', $session_id)->firstOrFail();
        } else {
            $this->session = Session::ownedByPartneship()
                ->where('ends_on', '>', \Carbon\Carbon::now())
                ->where('inscription_starts_on', '<', \Carbon\Carbon::now())
                ->where('inscription_ends_on', '>', \Carbon\Carbon::now())
                ->where('id', $session_id)->firstOrFail();
        }
        $this->checkTpvCompatibility();
    }

    /**
     * Every session has its Rates on sale. Each of these rates have a maximum
     * number allowed to be bought in a single cart.
     *
     * This method check that the booked number of tickets does not exceed the
     * maximum allowed. If it does, the maximum allowed is applied
     *
     * @param array $positions
     * @return array
     */
    private function applyMaxAllowedInscriptions(&$positions)
    {
        foreach ($positions as &$position) {
            // one session only has once a given Rate, so we can use first()
            // ie: Session #X has, at most, Rate #Y once
            $max_per_order = $this->session->rates()->where('rates.id', $position['rate_id'])->first()->count_free_positions;
            $position['quantity'] = min([isset($position['quantity']) ? $position['quantity'] : 0, $max_per_order]);
        }

        return $positions;
    }

    private function createInscriptions(array $inscriptions, array $params)
    {
        foreach ($inscriptions as $inscription) {
            if (isset($inscription['is_numbered']) && $inscription['is_numbered']) {

                // Next call saves data without checking anything.
                //
                // TODO we need to check availability and coherence (is this rate
                // available for this slot? or zone or session?, has this session
                // this slot_id available? etc.
                //

                // Check if there are available tickets for the session
                // TicketOffice allow sell more tickets
                if (!$this->private_usage && $this->session->count_free_positions <= 0) {
                    // ignore ticket
                    // TODO? throw ApiException
                } else {
                    $this->bookNumberedPosition($inscription, $params);
                }
            } else {
                // we need to verify if there is really availability for the
                // session and positions wanted
                $this->applyMaxAllowedInscriptions($inscriptions);

                // at this point, after applyMaxAllowedInscriptions(), we may ensure that
                // no rate is requested for more tickets that the available for it.
                // But we could not garantee that the sum of all requested tickets per
                // rate does not exceed the free tickets of the current session.
                //
                // We check it now:
                if ($inscription['quantity'] > $this->session->count_free_positions) {
                    throw new \App\Exceptions\ApiException("There is not so many available tickets on this session");
                }

                // at this point $params['positions'] is requesting an acceptable number of tickets
                $this->bookUnumberedPositions($inscription, $params);
            }
        }

        // autolock slots
        if ($this->session->autolock_type != null) {
            $this->autolock();
        }
    }

    private function bookNumberedPosition($inscription, $params)
    {

        $rate = Rate::find($inscription['rate_id']);
        $gift_code = isset($inscription['gift_code']) ? $inscription['gift_code'] : null;
        $code_dni = isset($inscription['code']) ? $inscription['code'] : null;

        // verify to not exceed rates limit for order
        // TicketOffice allow sell more tickets
        if (!$this->private_usage && !$this->checkMaxOrder($this->cart, $this->session, $rate))
            return;

        // we check if rate inscription is able to be added to cart
        if ($rate->validator_class && !\App\Services\Rate\CodeValidatorFactory::getInstance($rate)->canBeAddedToCart($this->cart, $this->session))
            return;

        $slot = Slot::findOrFail($inscription['slot_id']);

        $slot->pivot_session_id = $this->session->id;

        if ($slot->zone) {
            $slot->zone->pivot_session_id = $slot->pivot_session_id;
        }

        // Busca el rate en orden Slot -> Zona -> Sesión
        $rate = $slot->rates()->where('rates.id', $inscription['rate_id'])->first()
            ?? $slot->zone?->rates()->where('rates.id', $inscription['rate_id'])->first()
            ?? $slot->session->rates()->where('rates.id', $inscription['rate_id'])->first();

        // verify to not exceed max_on_sale rates limit
        // TicketOffice allow sell more tickets
        if (!$this->private_usage && $rate->calculeFreePositions() < 1)
            return;

        if (!$slot->isAvailableFor($this->session->id, $this->private_usage, $this->is_for_a_pack)) {
            throw new \App\Exceptions\ApiException(sprintf("Slot ID %s is not available for Session ID %s", $slot->id, $this->session->id), \App\Exceptions\ApiException::SLOT_IS_UNAVAILABLE);
        }

        $inscription = new Inscription();
        $inscription->session()->associate($this->session);
        $inscription->slot_id = $slot->id;
        $inscription->rate_id = $rate->id;
        $inscription->price = $rate->pivot->price;
        $inscription->price_sold = $rate->pivot->price;
        $inscription->code = $code_dni;

        if (array_key_exists('metadata', $params) && isset($params['metadata'][$rate->id])) {
            $inscription->metadata = json_encode($params['metadata'][$rate->id][$slot->id]);
        }

        $this->inscriptions->push($inscription);
        $this->cart->inscriptions()->save($inscription);

        if ($inscription->rate()->get()->first()->has_rule) {
            $rparams = explode(':', $inscription->rate()->get()->first()->rule_parameters);

            if ($rparams[0] == 'need' && $this->cart->inscriptions()->where('rate_id', $inscription->rate_id)->get()->count() % $rparams[1] == 0) {
                $price = $rate->pivot->price;
                $inscription->price_sold = round($price * $rparams[1], 1, PHP_ROUND_HALF_UP) - ($price * $rparams[1]) + $price;
                $inscription->save();
            }
        }

        if ($this->session->autolock_type != null) {
            $this->prepareAutolock($inscription, $slot);
        }

        $this->useGiftCard($gift_code, $inscription);
    }

    /**
     * Check limit max tickets rate in one cart
     * @return boolean 
     */
    private function checkMaxOrder(Cart $cart, Session $session, $rate)
    {
        $assignated_rate = AssignatedRate::whereSessionId($session->id)
            ->whereRateId($rate->id)
            ->first();

        $max_per_order = $assignated_rate->max_per_order ?? 0;
        $current_rates_in_cart = $cart->inscriptions()->whereSessionId($session->id)->whereRateId($rate->id)->count();

        return $current_rates_in_cart < $max_per_order;
    }

    private function bookUnumberedPositions($position, $params)
    {
        $gift_code = isset($position['gift_code']) ? $position['gift_code'] : null;
        for ($i = 0; $i < $position['quantity']; $i++) {
            $inscription = new Inscription();
            $inscription->session()->associate($this->session);

            if (isset($position['rate_id'])) {
                $rate = $this->session->rates->keyBy('id')->find($position["rate_id"]);

                // we check if rate inscription is able to be added to cart                
                if ($rate->validator_class && !\App\Services\Rate\CodeValidatorFactory::getInstance($rate)->canBeAddedToCart($this->cart, $this->session))
                    continue;

                $inscription->rate()->associate($rate);
                $inscription->price = $rate->pivot->price;
                $inscription->price_sold = $rate->pivot->price;
                //Si tienen codes
                if (isset($position['codes']) && isset($position['codes'][$i])) {
                    $inscription->code = $position['codes'][$i];
                }


                if (array_key_exists('metadata', $params) && isset($params['metadata'][$position['rate_id']])) {
                    $inscription->metadata = json_encode($params['metadata'][$position['rate_id']][$i]);
                }
            }
            $this->inscriptions->push($inscription);
            $this->cart->inscriptions()->save($inscription);

            if ($inscription->rate()->get()->first()->has_rule) {
                $rparams = explode(':', $inscription->rate()->get()->first()->rule_parameters);

                if ($rparams[0] == 'need' && $this->cart->inscriptions()->where('rate_id', $inscription->rate_id)->get()->count() % $rparams[1] == 0) {
                    $price = $rate->pivot->price;
                    $inscription->price_sold = round($price * $rparams[1], 1, PHP_ROUND_HALF_UP) - ($price * $rparams[1]) + $price;
                    $inscription->save();
                }
            }

            $this->useGiftCard($gift_code, $inscription);
        }
    }

    private function useGiftCard($code, $inscription)
    {
        // For now only form TicketOffice
        if ($this->private_usage && $code) {
            // serach gift code
            $card = GiftCard::ownedByPartneship()
                ->where('code', $code)
                ->doesntHave('inscription')
                ->first();

            if (!$card) {
                throw new \App\Exceptions\ApiException(sprintf(
                    "Code %s not found or already claimed",
                    $code
                ));
            }

            $inscription->price_sold = 0;
            $inscription->gift_card_id = $card->id;
            $inscription->save();
        }
    }

    private function checkTpvCompatibility()
    {
        // if used from frontend we cannot mix different TPVs in the same cart
        if (!$this->private_usage && $this->cart->inscriptions->first() && $this->cart->inscriptions->first()->session->tpv->id != $this->session->tpv->id) {
            throw new \App\Exceptions\ApiException(sprintf(
                "Session ID %s is using TPV ID %s, while current cart ID %s is using TPV ID %s",
                $this->session->id,
                $this->session->tpv->id,
                $this->cart->id,
                $this->cart->inscriptions->first()->session->tpv->id
            ));
        }
    }

    private function prepareAutolock($inscription, $slot)
    {
        switch ($this->session->autolock_type) {
            case Session::AUTOLOCK_CROSS:
                $this->lockCross($inscription, $slot);
                break;
            case Session::AUTOLOCK_RIGHT_LEFT:
                $this->lockRightLeft($inscription, $slot);
                break;
        }
    }

    private function autolock()
    {
        //slots in selected by user
        $slotsSelected = $this->slots_to_block->pluck('slot')->toArray();
        foreach ($this->slots_to_block as $aux) {
            $query = Slot::whereNotIn('id', $slotsSelected)->where(function ($q) {
                $q->where('space_id', $this->session->space_id);
            });

            $query->where(function ($q) use ($aux) {
                foreach ($aux->list as $l) {
                    $q->orWhere(function ($q) use ($l) {
                        $q->where('x', $l->x)
                            ->Where('y', $l->y);
                    });
                }
            });

            $slots = $query->get();

            foreach ($slots as $s) {
                //if ($s->isAvailableFor($this->session->id, $this->private_usage)) {
                //\App\SessionTempSlot::updateOrCreate([
                SessionTempSlot::create([
                    'session_id' => $this->session->id,
                    'slot_id' => $s->id,
                    'inscription_id' => $aux->inscription_id,
                    'cart_id' => $this->cart->id,
                    'expires_on' => $this->cart->expires_on,
                    'status_id' => 4 // covid19,
                ]);
                //}
                //Si el slot existe en la session como estado reservado en la misma session, lo eliminamos de session slot para no contabilizarlo dos veces
                $session_slot = SessionSlot::where('session_id', $this->session->id)->where('slot_id', $s->id)->where('status_id', 3)->first();
                if ($session_slot != null) {
                    $session_slot->delete();
                }
            }
        }
    }

    private function lockCross($inscription, $slot)
    {
        $n = $this->session->autolock_n;
        $aux = (object) [
            'inscription_id' => $inscription->id,
            'slot' => $slot->id,
            'list' => collect()
        ];

        for ($i = 1; $i <= $n; $i++) {
            // get Right
            $aux->list->push((object) ['x' => $slot->x + $i, 'y' => $slot->y]);

            // get Left
            $aux->list->push((object) ['x' => $slot->x - $i, 'y' => $slot->y]);

            // get Top
            $aux->list->push((object) ['x' => $slot->x, 'y' => $slot->y + $i]);

            // get Bottom
            $aux->list->push((object) ['x' => $slot->x, 'y' => $slot->y - $i]);
        }

        $this->slots_to_block->push($aux);
    }

    private function lockRightLeft($inscription, $slot)
    {
        $n = $this->session->autolock_n;
        $aux = (object) [
            'inscription_id' => $inscription->id,
            'slot' => $slot->id,
            'list' => collect()
        ];

        for ($i = 1; $i <= $n; $i++) {
            // get Right
            $aux->list->push((object) ['x' => $slot->x + $i, 'y' => $slot->y]);

            // get Left
            $aux->list->push((object) ['x' => $slot->x - $i, 'y' => $slot->y]);
        }

        $this->slots_to_block->push($aux);
    }
}
