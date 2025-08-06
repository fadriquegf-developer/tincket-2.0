<?php

namespace App\Services\Api;

use App\Models\Cart;
use App\Models\Pack;
use App\Models\PackRule;
use App\Models\GroupPack;
use App\Models\Inscription;


class PackService extends AbstractService
{

    /** @var \Illuminate\Support\Collection */
    private $inscriptions;
    private $number_of_tickets = 0;
    private $price_without_discount = 0;

    /** @var Pack */
    private $pack;

    /** @var Cart */
    private $cart;

    public function __construct()
    {
        $this->clear();
    }

    public function clear()
    {
        $this->inscriptions = collect();
        $this->number_of_tickets = 0;
        $this->price_without_discount = 0;
    }

    public function setInscriptions(\Illuminate\Support\Collection $inscriptions)
    {
        $this->inscriptions = $inscriptions;
        $this->number_of_tickets = $inscriptions->count();
        $this->price_without_discount = $inscriptions->sum('price');
    }

    /**
     * @param Pack $pack
     * @return PackService $this
     */
    public function setPack(Pack $pack)
    {
        $this->pack = $pack;

        return $this;
    }

    /**
     * @param Cart $cart
     * @return PackService $this
     */
    public function setCart(Cart $cart)
    {
        $this->cart = $cart;

        return $this;
    }

    /**
     * When all the sets of Inscriptions are added in this class, a Pack may be applied
     * and the specific rule will be applied
     * 
     * @throws \Exception
     */
    public function applyPack()
    {
        if (!$this->pack || !$this->cart) {
            throw new \Exception("There is no pack or cart to apply rules");
        }


        if ($this->pack->one_session_x_event) {
            $one_session_x_event = false;
            $events = [];

            $this->inscriptions->each(function (Inscription $inscription) use (&$one_session_x_event, &$events) {
                $event = $inscription->session->event ?? '';
                if (in_array($event, $events)) {
                    $one_session_x_event = true;
                }

                $events[] = $event;
            });

            if ($one_session_x_event) {
                throw new \Exception("Breach of the rule only one session for event");
            }
        }

        $rule = $this->selectRule();

        if ($rule->percent_pack) {
            $ratio = (100 - $rule->percent_pack) / 100;
        } else if ($rule->price_pack) {
            // Division by zero
            if ($this->price_without_discount > 0) {
                $ratio = $rule->price_pack / $this->price_without_discount;
            } else {
                $ratio = $rule->price_pack / $this->inscriptions->count();
            }
        } else {
            throw new \App\Exceptions\ApiException(sprintf("The Rule ID %s is no appliable neither percent nor price format", $rule->id));
        }

        $group_pack = GroupPack::create(['pack_id' => $this->pack->id, 'cart_id' => $this->cart->id]);

        $this->inscriptions->each(function (Inscription $inscription) use ($rule, $ratio, $group_pack) {
            $inscription->price_sold = round($inscription->price * $ratio, 4); // DB precision

            if ($this->pack->round_to_nearest && $rule->percent_pack) {
                $cost = $inscription->price_sold;
                $factor = 0.5;
                $cost = ($cost / $factor);
                $cost = round($cost) * $factor;
                $inscription->price_sold = $cost;
            }

            $inscription->save();

            //$inscription->origin()->associate($group_pack)->save();
            $inscription->group_pack_id = $group_pack->id;
            $inscription->save();
        });
    }

    /**
     * Devuelve la regla que coincide con el nº de entradas seleccionadas.
     *
     * @throws \App\Exceptions\ApiException
     */
    private function selectRule(): PackRule
    {
        $tickets = (int) $this->number_of_tickets;

        /** @var \Illuminate\Support\Collection<int, PackRule> $rules */
        $rules = $this->pack->relationLoaded('rules')
            ? $this->pack->rules
            : $this->pack->load('rules')->rules;

        $matched = $rules->first(function (PackRule $rule) use ($tickets): bool {
            return
                $rule->number_sessions === $tickets
                || ($rule->all_sessions && $tickets === $this->pack->sessions->count());
        });

        if ($matched === null) {
            throw new \App\Exceptions\ApiException(
                sprintf('Pack #%d no admite %d inscripciones.', $this->pack->id, $tickets)
            );
        }

        return $matched;
    }

}
