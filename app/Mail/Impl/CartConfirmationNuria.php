<?php

namespace App\Mail\Impl;

/**
 * Confirmation email for Torelló Mountain Film
 */
class CartConfirmationNuria extends \App\Mail\AbstractCartConfirmation
{

    protected function setMailMetadata()
    {
        $this->subject("Confirmación de compra Teatre Núria Espert" . $this->cart->confirmation_code);
        $this->from('noreply@teatrenuriaespert.cat', 'Teatre Núria Espert');
        $this->replyTo('info@teatrenuriaespert.cat');
    }

}
