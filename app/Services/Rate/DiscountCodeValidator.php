<?php

namespace App\Services\Rate;

use App\Models\Cart;
use App\Models\Session;
use App\Models\AssignatedRate;


class DiscountCodeValidator extends CodeValidatorAbstract
{

    public function isCodeValid(Session $session, $code): bool
    {
        $assignated_rate = AssignatedRate::whereSessionId($session->id)
            ->whereRateId($this->rate->id)
            ->first();

        $is_valid = json_decode($assignated_rate->validator_class)->attr->code == $code;

        if (!$is_valid)
            $this->message = "El codi $code no és vàlid"; // TODO needs to translate

        return $is_valid;
    }

    public function canBeAddedToCart(Cart $cart, Session $session): bool
    {
        $assignated_rate = AssignatedRate::whereSessionId($session->id)
            ->whereRateId($this->rate->id)
            ->first();

        $max_per_user = json_decode($assignated_rate->validator_class)->attr->max_per_user ?? 0;
        $current_rates_in_cart = $cart->inscriptions()->whereSessionId($session->id)->whereRateId($this->rate->id)->count();

        return $current_rates_in_cart < $max_per_user;
    }

}
