<?php

namespace App\Services\Api;

use App\Models\Cart;
use App\Models\Pack;
use App\Models\Client;
use App\Models\Session;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class CartService extends AbstractService
{

    public function createCart(Request $request): Cart
    {
        $cart = DB::transaction(function () use ($request) {
            $cart = new Cart();
            $cart->token = Str::uuid()->toString();
            $cart->brand()->associate($request->get('brand'));
            $cart->seller()->associate($request->get('user'));
            $cart->extendTime();
            $cart->save();

            return $cart;
        });

        return $cart->fresh();
    }

    /**
     * Used to set a batch of Inscriptions to a Cart. Unlike setSlot which sets
     * a single slot or inscription to a Cart.
     *
     * @param Cart $cart
     * @param Request $request
     * @return Cart
     */
    public function setInscriptions(Cart $cart, Request $request)
    {
        // cart_id is not needed in params, in anycase Cart id of the URL will be used
        $params = $request->all();

        $params['cart_id'] = $cart->id;

        $inscriptions_service = \App::make(InscriptionService::class);

        $inscriptions_service->removeOldInscriptionsSet($params);

        $inscriptions_service->createNewInscriptionsSet($params);

        return $inscriptions_service->getCart();
    }

    /**
     * Creates a Pack with their Inscriptions and attach it to the current cart.
     *
     * @param Cart $cart
     * @param Request $request
     */
    public function setPack(Cart $cart, Request $request)
    {
        \DB::beginTransaction();
        // TODO check if data is coherent with pack definition
        $pack = Pack::ownedByBrand()->findOrFail($request->get('pack_id'));
        $pack_multiplier = min((int) $request->get('pack_multiplier', 1), $pack->max_per_cart);

        $packsInscriptions = [];
        for ($i = 0; $i < $pack_multiplier; $i++) {
            $packsInscriptions[$i] = collect();
        }

        $inscriptions_service = \App::make(InscriptionService::class);
        if ($this->private_usage)
            $inscriptions_service->enablePrivateUsage();

        // create all inscriptions together to correct autolock slots
        foreach ($request->get('selection', []) as $inscriptions) {
            // prepare inscriptions_service
            $inscriptions_service->clear();

            $session = Session::findOrFail($inscriptions['session_id']);
            // we prepare params as Service expects
            $params = [
                'cart_id' => $cart->id,
                'session_id' => $session->id,
                'inscriptions' => []
            ];

            $isNumbered = $inscriptions['is_numbered'] ?? false;

            if ($isNumbered) {
                foreach ($inscriptions['slots'] as $slot) {

                    $params['inscriptions'][] = [
                        'is_numbered' => $isNumbered,
                        'rate_id' => $session->generalRate->rate_id,
                        'slot_id' => isset($slot['id']) ? $slot['id'] : $slot, //Añadimos el isset por si no tienen cliente actualizado
                        'code' => isset($slot['code']) ? $slot['code'] : NULL //Añadimos el isset por si no tienen cliente actualizado
                    ];
                }
            } else {
                $params['inscriptions'][] = [
                    'is_numbered' => $isNumbered,
                    'quantity' => $pack_multiplier,
                    'codes' => isset($inscriptions['codes']) ? $inscriptions['codes'] : null,
                    'rate_id' => $session->generalRate->rate_id
                ];
            }


            $newInscriptions = $inscriptions_service->createNewInscriptionsSet($params, true);
            $i = 0;
            // split inscriptions in packs
            foreach ($newInscriptions as $inscription) {
                $packsInscriptions[$i]->push($inscription);
                $i++;
            }
        }

        // assignar inscriptions to pack
        foreach ($packsInscriptions as $inscriptions) {
            $pack_service = \App::make(PackService::class);
            $pack_service->setInscriptions($inscriptions);
            $pack_service->setPack($pack)->setCart($cart)->applyPack();
        }
        \DB::commit();

        return null;
    }

    public function setGiftCard(Cart $cart, Request $request)
    {
        $params = $request->all();

        $gift_service = \App::make(GiftCardService::class);
        $gift_service->setCart($cart)->createGifts($params);
        return true;
    }

    public function setClient(Cart $cart, Request $request)
    {
        $cart->checkBrandOwnership();
        $client_id = $request->get('client_id');
        $client = Client::ownedByBrand()
            ->where('id', $client_id)
            ->where('email', $request->get('email'))
            ->first();

        if (!$client) {
            throw new \App\Exceptions\ApiException("Invalid Client (ID: $client_id) to associate with Cart ID $cart->id");
        }
        $cart->client()->associate($client)->save();

        return $cart->fresh();
    }

    /**
     * Set Slot in current Cart
     * Expected request:
     *
     * {
     *  "cart_id": 11,
     * 	"session_id": 1,
     *  "position":
     *    {
     *      "slot_id": 5,
     *      "rate_id": null
     * 	  }
     *  }
     *
     * @param Cart $cart
     * @param Request $request
     */
    public function setSlots(Cart $cart, Request $request)
    {

        // cart_id is not needed in params, in anycase Cart id of the URL will be used
        $params = $request->all();

        $params['cart_id'] = $cart->id;

        $inscriptions_service = \App::make(InscriptionService::class);

        $inscriptions_service->createNewInscriptionsBySlot($params);

        return $inscriptions_service->getCart();
    }

}
