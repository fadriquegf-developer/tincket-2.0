<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Payment;
use App\Models\Cart;

class ErrorDuplicate extends Mailable
{

    use Queueable,
        SerializesModels;

    /** @var Payment */
    public $payment;

    /** @var Cart */
    public $cart;

    /** @var Cart */
    public $duplicated_cart;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    final public function __construct(Payment $payment, Cart $cart, Cart $duplicated_cart)
    {
        $this->payment = $payment;

        $this->cart = $cart;

        $this->duplicated_cart = $duplicated_cart;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    final public function build()
    {
        // unique per brand, needs to be implemented in child classes
        $this->setMailMetadata();
        // common on all brands
        $this->createMail();

        return $this;
    }

    protected function setMailMetadata()
    {
        $this->subject(__('backend.cart.error_duplicate_subject'));
        $this->bcc(['gemma.javajan@gmail.com', 'adria@javajan.com', 'fadrique@javajan.com']);

        if (brand_setting('mail.replyto'))
            $this->replyTo(brand_setting('mail.replyto'));
    }

    private function createMail()
    {
        $this->view('core.emails.error_duplicate');
    }
}
