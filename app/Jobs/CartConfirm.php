<?php

namespace App\Jobs;

use TCPDI;
use App\Models\Cart;
use App\Models\GiftCard;
use App\Models\GroupPack;
use App\Mail\GiftCardMail;
use App\Models\Inscription;
use Illuminate\Bus\Queueable;
use setasign\Fpdi\Fpdi;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CartConfirm implements ShouldQueue
{

    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    protected $cart;
    protected $hasToSendEmail;
    protected $params;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Cart $cart, $params = [])
    {
        $this->cart = $cart;
        $this->hasToSendEmail = $params['send_mail'] ?? true;
        $this->params = $params;
    }

    /**
     * Handle the event.
     * 
     * @return void
     */
    public function handle()
    {
        // because this may be called from a third thread, so API Header key
        // to identify the brand will not be received, we need to set
        // base config to render proper PDF design according to the brand
        (new \App\Http\Middleware\CheckBrandHost())->loadBrandConfig($this->cart->brand->code_name);

        // Confirm temp slots
        $this->cart->confirmTempSlot();

        $this->storeInscriptionsPdf($this->cart);

        $this->storePacksPdf($this->cart);

        $this->storeGiftCardsPdf($this->cart);

        //if ($this->hasToSendEmail) $this->sendConfirmationEmail($this->cart);
    }

    /**
     * We send the sending email. It is not queued since it is already 
     * executed in a queue, so we use the same thread
     * 
     * @param Cart $cart
     */
    /* public function sendConfirmationEmail(Cart $cart)
    {
        if (isset($cart->client)) {
            app()->setLocale($cart->client->locale);
        }

        $mailer = (new \App\Services\MailerBrandService($cart->brand->code_name))->getMailer();
        $mailer_class = brand_setting('mail.confirmation_class');
        $mailer->to(trim($cart->client->email))->send(new $mailer_class($cart));

        // send gift card to friends
        foreach ($cart->gift_cards()->hasEmail()->get() as $gift) {
            $mailer->to(trim($gift->email))->send(new GiftCardMail($gift));
        }
    } */

    private function storeInscriptionsPdf($cart)
    {
        $batchSize = 5; // Reducir a 5 PDFs por lote
        $inscriptions = $cart->inscriptions; // debería ser colección

        if (!$inscriptions instanceof \Illuminate\Support\Collection) {
            $inscriptions = $inscriptions->get(); // cargar colección desde relación
        }

        $chunks = $inscriptions->chunk($batchSize);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $inscription) {
                $this->storeInscriptionPdf($inscription);
            }
            sleep(5);
        }
    }

    /**
     * Stores, if not exists, the inscription PDF for the given Inscription
     * 
     * @param Inscription $inscription
     * @return mixed path of created pdf or false if already created
     * @throws \Exception
     */
    private function storeInscriptionPdf($inscription)
    {
        if (isset($inscription->cart->client)) {
            app()->setLocale($inscription->cart->client->locale);
        }

        //Comentamos para la regeneracion
        /* if ($inscription->pdf)
        {
            $inscription->pdf;
        } */


        // This nasty thing it's because one client has a borken barcode reader
        // whose doesn't recognise them well or they dont know how to configure it.
        // Numbers cant be used on the random string.
        // Ask managers to explain why this is here and dont change it!
        // $inscription->barcode = str_random(13);
        if (!$inscription->barcode) {
            $inscription->barcode = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 13);
        }

        $inscription->save();

        $destination_path = brand_setting('base.inscription.pdf_folder');
        $pdfParams = $this->params['pdf'] ?? [];

        if (!is_array($pdfParams)) {
            $pdfParams = [];
        }

        $pdf_url = env('TK_PDF_RENDERER', 'https://pdf.yesweticket.com') .
            "/render?url=" . url(route('open.inscription.pdf', array_merge(
                [
                    'inscription' => $inscription,
                    'token' => $inscription->cart->token
                ],
                $pdfParams
            )));

        try {
            if (\Storage::disk()->put("$destination_path/$inscription->pdf_name", fopen($pdf_url, 'r'))) {
                # we need the magic string "/app" due to this bug: https://github.com/laravel/framework/issues/13610
                $inscription->pdf = "app/$destination_path/$inscription->pdf_name";
                $inscription->save();
            }
        } catch (\Exception $e) {
            \Log::error("TICKET of Inscription ID $inscription->id failed", ["exception" => $e]);
            if (app()->environment() === 'production')
                throw $e;
        }

        return $inscription->pdf;
    }

    private function storePacksPdf(Cart $cart)
    {
        foreach ($cart->groupPacks as $group_pack) {
            $this->storePackPdf($group_pack);
        }
    }

    private function storePackPdf(GroupPack $pack)
    {
        //Comentamos para la regeneracion
        /* if ($pack->pdf)
        {
            return;
        } */

        foreach ($pack->inscriptions as $inscription) {
            $pdfs[] = $this->storeInscriptionPdf($inscription);
        }

        $tmp_file = $this->mergePdfs($pdfs);

        $destination_path = config()->get('base.packs.pdf_folder');
        if (\Storage::disk()->put("$destination_path/$pack->pdf_name", fopen($tmp_file, 'r'))) {
            # we need the magic string "/app" due to this bug: https://github.com/laravel/framework/issues/13610
            $pack->pdf = "app/public/$destination_path/$pack->pdf_name";
            $pack->save();
        }
        unlink($tmp_file);
    }

    private function storeGiftCardsPdf(Cart $cart)
    {
        foreach ($cart->gift_cards as $gift_card) {
            $this->storeGiftCardPdf($gift_card);
        }
    }

    private function storeGiftCardPdf(GiftCard $gift)
    {
        // generate gift card codes
        if (!$gift->code) {
            $gift->generateCode();
        }

        // create pdfs
        $destination_path = brand_setting('base.gift_card.pdf_folder');
        $pdf_url = env('TK_PDF_RENDERER', 'https://pdf.yesweticket.com') .
            "/render?url=" . url(route('open.gitf_card.pdf', array_merge(
                [
                    'gift' => $gift,
                    'token' => $gift->cart->token
                ],
                (['ph' => 140, 'pw' => 240, 'mb' => 0, 'mt' => 5, 'ml' => 5, 'mr' => 5])
            )));

        try {
            if (\Storage::disk()->put("$destination_path/$gift->pdf_name", fopen($pdf_url, 'r'))) {
                # we need the magic string "/app" due to this bug: https://github.com/laravel/framework/issues/13610
                $gift->pdf = "app/public/$destination_path/$gift->pdf_name";
                $gift->save();
            }
        } catch (\Exception $e) {
            \Log::error("Gift card ID $gift->id failed", ["exception" => $e]);
            if (app()->environment() === 'production')
                throw $e;
        }

        return $gift->pdf;
    }

    private function mergePdfs(array $pdfs)
    {
        $pdf = new Fpdi();

        foreach ($pdfs as $file) {
            if (!file_exists($file) || !is_file($file)) {
                \Log::warning("Archivo PDF no válido para merge: {$file}");
                continue;
            }
            try {
                $pageCount = $pdf->setSourceFile($file);
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $tplIdx = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($tplIdx);
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($tplIdx);
                }
            } catch (\Exception $e) {
                \Log::error("Error fusionando PDF {$file}: " . $e->getMessage());
            }
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'merged_pdf_') . '.pdf';

        $pdf->Output($tmpFile, 'F');

        return $tmpFile;
    }
}
