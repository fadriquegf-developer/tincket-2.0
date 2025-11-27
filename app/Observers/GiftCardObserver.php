<?php

namespace App\Observers;

use App\Models\GiftCard;
use App\Services\InscriptionService;

class GiftCardObserver
{
    public function created(GiftCard $giftCard)
    {
        $this->invalidateCache($giftCard);
    }

    public function updated(GiftCard $giftCard)
    {
        if ($giftCard->isDirty(['price', 'cart_id'])) {
            $this->invalidateCache($giftCard);
        }
    }

    public function deleted(GiftCard $giftCard)
    {
        $this->invalidateCache($giftCard);
    }

    private function invalidateCache(GiftCard $giftCard)
    {
        if ($giftCard->cart_id && $giftCard->cart) {
            app(InscriptionService::class)->invalidateCartCache($giftCard->cart);
        }
    }
}
