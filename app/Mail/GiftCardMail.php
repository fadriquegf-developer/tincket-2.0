<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\GiftCard;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GiftCardMail extends Mailable
{

    use Queueable,
        SerializesModels;

    /** @var GiftCard */
    public $gift;

    /** @var Event */
    public $event;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    final public function __construct(GiftCard $gift)
    {
        $this->gift = $gift;

        $this->event = $gift->event;
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
        $this->subject(__('backend.cart.gift_card_subject') . ' ' . $this->event->name);

        if (brand_setting('mail.replyto'))
            $this->replyTo(brand_setting('mail.replyto'));
    }

    private function createMail()
    {
        $this->view('core.emails.gift_card', ['gift' => $this->gift, 'brand' => $this->gift->brand]);

        // attatch gift card
        if ($this->gift->pdf && \Storage::disk('local')->exists($this->gift->pdf)) {
            $this->attach(\Storage::disk('local')->path($this->gift->pdf), ['mime' => 'application/pdf']);
        }
    }
}
