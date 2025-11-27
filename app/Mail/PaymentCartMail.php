<?php

namespace App\Mail;

use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentCartMail extends Mailable
{
    use Queueable, SerializesModels;

    public $cart;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->subject("Pagament carrito ".$this->cart->confirmation_code." per entrades a ".$this->cart->client->brand->name);
        $this->from('noreply@yesweticket.com', 'YesWeTicket');
        $this->replyTo('noreply@yesweticket.com');
        $this->cc(['gemma.javajan@gmail.com']);
        $this->view(brand_setting('base.emails.email-payment'), ['cart' => $this->cart, 'client' => $this->cart->client,'brand' => $this->cart->client->brand]);

        return $this;
    }
}
