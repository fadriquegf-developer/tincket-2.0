<?php

namespace App\Mail\Impl;

/**
 * Confirmation email for Torelló Mountain Film
 */
class CartConfirmationTmf extends \App\Mail\AbstractCartConfirmation
{

    protected function setMailMetadata()
    {
        $this->subject("Confirmación de compra Torello Mountain Film " . $this->cart->confirmation_code);
        $this->from('tickets@torellomountainfilm.cat', 'Torelló Mountain Film');
        $this->replyTo('info@torellomountainfilm.cat');
    }

}
