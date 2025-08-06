<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{

    use Queueable,
        SerializesModels;

    public $client;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // needs to be able to be overriden by brand
        $this->subject("Reset de password");
        $this->from('noreply@yesweticket.com', 'YesWeTicket');
        $this->replyTo('noreply@yesweticket.com');
        $this->view(sprintf(brand_setting('base.emails.reset-password'), $this->client->locale), ['client' => $this->client, 'brand' => $this->client->brand]);

        return $this;
    }

}
