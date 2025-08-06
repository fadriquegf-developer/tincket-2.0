<?php

namespace App\Services\Rate;

use App\Models\Cart;
use App\Models\Session;


interface CodeValidatorInterface
{

    /**
     * Check if a Rate for a Session can be added to the given cart
     * @param Cart $cart     
     * @param Session $session
     * @return bool
     */
    public function canBeAddedToCart(Cart $cart, Session $session);

    /**
     * Check if the code applied to the session is valid     
     * @param Session $session
     * @param string $code
     * @return bool
     */
    public function isCodeValid(Session $session, $code);

    /**
     * Returns the output message of the lastest executed operation
     */
    public function getMessage();
}
