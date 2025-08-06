<?php

namespace App\Mail\Impl;

/**
 * Default confirmation email
 */
class DefaultCartConfirmation extends \App\Mail\AbstractCartConfirmation
{

    protected function setMailMetadata()
    {
        $this->subject(__('backend.cart.cart_confirmation')." " . $this->cart->confirmation_code);

        if (brand_setting('mail.replyto'))
            $this->replyTo(brand_setting('mail.replyto'));
    }

}
