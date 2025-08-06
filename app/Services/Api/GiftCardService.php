<?php

namespace App\Services\Api;

use App\Models\Cart;
use App\Models\Event;

/**
 * GiftCardService
 *
 */
class GiftCardService extends AbstractService
{
    /** @var Cart */
    private $cart;

    
    public function setCart(Cart $cart)
    {
        $this->cart = $cart;

        return $this;
    }

    /**
     * Creates a set of GiftCard and add them to the Cart.
     *
     * Returns a Collection of the created GiftCard
     *
     * @param array $params needs:
     * All field are mandatory, Controller must ensure to call Service correctly.
     * Example of params:
     *  {
     *      "event_id": 1,
     *      "gift_cards": [
     *          {
     *            "email":null,
     *          },
     *          {
     *            "email":"adria@javajan.com",
     *          },
     *      ]
     *  }
     * @return \Illuminate\Support\Collection
     */
    public function createGifts(array $params)
    {
        if (!isset($params['event_id']) || !isset($params['gift_cards'])) {
            throw new \App\Exceptions\ApiException(sprintf(
                "Missing paramters to create Gift Cards"
            ));
        }
        // search event
        $event = Event::published()->ownedByPartneship()->where('id', $params['event_id'])->first();

        // check if has gift card enable
        if (!($event && $event->enable_gift_card)) {
            throw new \App\Exceptions\ApiException(sprintf(
                "Gift card not enabled"
            ));
        }

        $gifts = collect();
        \DB::transaction(function () use ($params, $gifts, $event) {
            foreach ($params['gift_cards'] ?? [] as $gift) {
                $gifts->push($this->cart->gift_cards()->create([
                    'brand_id' => $event->brand_id,
                    'event_id' => $event->id,
                    'price' =>  $event->price_gift_card,
                    'email' => $gift['email'] ?? null,
                ]));
            }
        }, 2);

        return $gifts;
    }
}
