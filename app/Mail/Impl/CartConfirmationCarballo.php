<?php

namespace App\Mail\Impl;

/**
 * Confirmation email for Carballo
 */
class CartConfirmationCarballo extends \App\Mail\AbstractCartConfirmation
{

    protected function setMailMetadata()
    {
        $this->subject("Confirmación de compra Carballo " . $this->cart->confirmation_code);

        if (brand_setting('mail.replyto'))
            $this->replyTo(brand_setting('mail.replyto'));
    }

}
