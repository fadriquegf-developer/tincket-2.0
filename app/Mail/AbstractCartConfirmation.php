<?php

namespace App\Mail;

use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * This abstract class is responsible to render and send the email confirmation
 * to the client.
 * 
 * Anyway, some data as Subject, From receipt, etc. needs to be differents for
 * each brand. To do so this class needs to be extended per each brand setting 
 * this information
 * 
 * Extended classes <strong>must be named</strong> as CartConfirmationCodeName where
 * CodeName is the brand's code name studly cased
 */
abstract class AbstractCartConfirmation extends Mailable
{

    use Queueable,
        SerializesModels;

    /** @var Cart */
    public $cart;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    final public function __construct(Cart $cart)
    {
        $this->cart = $cart;
        $cart->load([
            'client',
            'inscriptions.session.event',
            'inscriptions.slot',
            'inscriptions.rate',
            'inscriptions.group_pack.pack',
            'inscriptions.group_pack.inscriptions',
            'inscriptions.session.event.brand',
            'inscriptions.session.space',
            'groupPacks.pack',
            'groupPacks.inscriptions.session.event',
            'groupPacks.inscriptions.slot',
            'groupPacks.inscriptions.rate',
            'groupPacks.inscriptions.group_pack.pack',
            'groupPacks.inscriptions.group_pack.inscriptions',
            'groupPacks.inscriptions.session.event.brand',
            'groupPacks.inscriptions.session.space',
            'gift_cards.event',
            'brand'
        ]);

        // because this may be called from a third thread, so API Header key
        // to identify the brand will not be received, we need to set
        // base config to render proper PDF design according to the brand
        // (new \App\Http\Middleware\CheckBrandHost())->loadBrandConfig($cart->brand->code_name);

        // TODO: because this is executed in a queue we may found that two differents
        // brands are trying to send email at same moment, each one for its smtp server.
        // If only one queue worker were in use, there was not problem, but if there are
        // more than one we may send some email throught non expected SMTP server.
        // 
        // One solution could be to use our own mail drive that accept configuration
        // parameter instead to use the default one which uses the config() function.
        // MailServiceProvider is a singleton so when config() is changed it does not
        // realize unless we re-register the provider
        //

        // re execute the MailServiceProvider that should use your new config        
        // (new \Illuminate\Mail\MailServiceProvider(app()))->register();
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

    /**
     * This fuction needs to be implemented for each Brand email confirmation 
     * class which will set the Subject, From, and so on attributes
     */
    abstract protected function setMailMetadata();

    private function createMail()
    {
        if (brand_setting('base.cart.views.email.html'))
            $this->view(sprintf(brand_setting('base.cart.views.email.html'), $this->cart->client->locale ?? 'ca'), ['cart' => $this->cart, 'brand' => $this->cart->brand]);

        // a plain text maybe specified. If not, the HTML view transformed to 
        // plain text will be used
        if (brand_setting('base.cart.views.email.plain'))
            $this->text(sprintf(brand_setting('base.cart.views.email.plain'), $this->cart->client->locale ?? 'ca'), ['cart' => $this->cart, 'brand' => $this->cart->brand]);


        if (config()->get('mail.merge_attachments')) {
            $this->attachInscriptionsInASinglePdf();
        } else {
            $this->attachInscriptions();
        }

        // attach gift cards
        $this->attachGiftCards();
    }

    /**
     * Attach PDF with inscriptions and packs receipts into email
     */
    private function attachInscriptions()
    {
        // attach pack receipts
        foreach ($this->cart->groupPacks as $pack) {
            $this->attach(base_path() . \Storage::url($pack->pdf), ['mime' => 'application/pdf']);
        }

        // attach inscriptions receipts
        foreach ($this->cart->inscriptions as $inscription) {
            $this->attach(base_path() . \Storage::url($inscription->pdf), ['mime' => 'application/pdf']);
        }
    }

    /**
     * Merge all cart and attach PDF with inscriptions and packs receipts into email
     */
    private function attachInscriptionsInASinglePdf()
    {
        //Version vieja con vista
        /* $inscriptions_from_single = $this->cart->inscriptions;
        $inscriptions_from_packs = $this->cart->groupPacks->pluck('inscriptions')->collapse();
        $inscriptions = collect([$inscriptions_from_single, $inscriptions_from_packs])->collapse(); */

        $inscriptions = $this->cart->allInscriptions;

        $merged_file = (new \App\Services\PDF\PDFService)->inscriptions($inscriptions, $this->cart->token);

        $this->attach($merged_file, ['mime' => 'application/pdf']);
    }

    /**
     * Attach PDF with gift cards without email
     */
    private function attachGiftCards()
    {
        foreach ($this->cart->gift_cards()->notHasEmail()->get() as $gift) {
            $this->attach(base_path() . \Storage::url($gift->pdf), ['mime' => 'application/pdf']);
        }
    }
}
