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

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Timeout apropiado para generación de PDFs
    public $timeout = 120;

    // Reintentos limitados para evitar loops infinitos
    public $tries = 3;

    // Esperar entre reintentos (exponencial backoff)
    public $backoff = [10, 30, 60];

    // Máximo de excepciones antes de fallar
    public $maxExceptions = 2;

    // Eliminar el job si el modelo ya no existe
    public $deleteWhenMissingModels = true;

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
        $this->onQueue('critical');
    }

    /**
     * Handle the event.
     * 
     * @return void
     */
    public function handle()
    {
        (new \App\Http\Middleware\CheckBrandHost())->loadBrandConfig($this->cart->brand->code_name);

        $this->cart->confirmTempSlot();
        $this->storeInscriptionsPdf($this->cart);
        $this->storePacksPdf($this->cart);
        $this->storeGiftCardsPdf($this->cart);
        $this->updateSlotsLockReason();
        if ($this->hasToSendEmail) {
            $this->sendConfirmationEmail($this->cart);
        }
    }

    private function updateSlotsLockReason()
    {
        $inscriptionsBySession = [];

        // ✅ FIX: Cargar inscripciones sin BrandScope
        $allInscriptions = $this->cart->inscriptions()
            ->withoutGlobalScope(\App\Scopes\BrandScope::class)
            ->get();

        foreach ($allInscriptions as $inscription) {
            if ($inscription->slot_id && $inscription->session_id) {
                if (!isset($inscriptionsBySession[$inscription->session_id])) {
                    $inscriptionsBySession[$inscription->session_id] = [];
                }
                $inscriptionsBySession[$inscription->session_id][] = $inscription->slot_id;
            }
        }

        // ✅ FIX: Cargar groupPacks y sus inscripciones sin BrandScope
        $groupPacks = $this->cart->groupPacks()
            ->withoutGlobalScope(\App\Scopes\BrandScope::class)
            ->get();

        foreach ($groupPacks as $groupPack) {
            $packInscriptions = $groupPack->inscriptions()
                ->withoutGlobalScope(\App\Scopes\BrandScope::class)
                ->get();

            foreach ($packInscriptions as $inscription) {
                if ($inscription->slot_id && $inscription->session_id) {
                    if (!isset($inscriptionsBySession[$inscription->session_id])) {
                        $inscriptionsBySession[$inscription->session_id] = [];
                    }
                    $inscriptionsBySession[$inscription->session_id][] = $inscription->slot_id;
                }
            }
        }

        // Actualizar session_slot para cada sesión
        foreach ($inscriptionsBySession as $sessionId => $slotIds) {
            $slotIds = array_unique($slotIds);

            foreach ($slotIds as $slotId) {
                \App\Models\SessionSlot::updateOrCreate(
                    [
                        'session_id' => $sessionId,
                        'slot_id' => $slotId
                    ],
                    [
                        'status_id' => 2, // 2 = Vendida
                        'comment' => null
                    ]
                );
            }

            // Invalidar cache de Redis
            $session = \App\Models\Session::find($sessionId);
            if ($session) {
                $redisService = new \App\Services\RedisSlotsService($session);
                $redisService->regenerateCache();
            }
        }
    }

    /**
     * We send the sending email. It is not queued since it is already 
     * executed in a queue, so we use the same thread
     * 
     * @param Cart $cart
     */
    public function sendConfirmationEmail(Cart $cart)
    {
        if (isset($cart->client)) {
            app()->setLocale($cart->client->locale);
        }

        $mailer = app(\App\Services\MailerService::class)->getMailerForBrand($cart->brand);
        $mailer_class = brand_setting('mail.confirmation_class');

        // client has email
        if (isset($cart->client->email)) {
            $mailer->to(trim($cart->client->email))->send(new $mailer_class($cart));
        }

        // send gift card to friends
        foreach ($cart->gift_cards()->hasEmail()->get() as $gift) {
            $giftEmail = trim($gift->email);
            if ($giftEmail) {
                $mailer->to($giftEmail)->send(new GiftCardMail($gift));
            }
        }
    }

    private function storeInscriptionsPdf($cart)
    {
        $batchSize = 5;

        // ✅ FIX: Usar el método de relación () y deshabilitar BrandScope
        $inscriptions = $cart->inscriptions()
            ->withoutGlobalScope(\App\Scopes\BrandScope::class)
            ->get();

        // ✅ Validar que hay inscripciones
        if ($inscriptions->isEmpty()) {
            \Log::warning('No inscriptions found for cart', [
                'cart_id' => $cart->id,
                'cart_brand_id' => $cart->brand_id
            ]);
            return;
        }

        $chunks = $inscriptions->chunk($batchSize);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $inscription) {
                // ✅ Extra validación defensiva
                if ($inscription) {
                    $this->storeInscriptionPdf($inscription);
                }
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

        if (!$inscription->barcode) {
            $inscription->barcode = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 13);
        }
        $inscription->save();

        \DB::commit();

        // ✅ FIX: Deshabilitar BrandScope al recargar la inscripción
        $inscription = Inscription::withoutGlobalScope(\App\Scopes\BrandScope::class)
            ->with(['cart'])
            ->find($inscription->id);

        if (!$inscription) {
            \Log::error("Inscription not found after save", ['id' => $inscription->id]);
            throw new \Exception("Inscription {$inscription->id} not found after save");
        }

        // Crear directorio si no existe
        $destination_path = 'pdf/inscriptions';
        $fullDirectoryPath = storage_path('app/' . $destination_path);
        if (!is_dir($fullDirectoryPath)) {
            mkdir($fullDirectoryPath, 0775, true);
            chmod($fullDirectoryPath, 0775);
        }

        $pdfParams = $this->params['pdf'] ?? [];
        if (!is_array($pdfParams)) {
            $pdfParams = [];
        }

        sleep(1);

        // 🔥 NUEVO: Generar URL interna
        $url = url(route('open.inscription.pdf', array_merge(
            [
                'inscription' => $inscription->id,
                'token' => $inscription->cart->token,
                'brand_code' => $inscription->cart->brand->code_name
            ],
            $pdfParams
        )));

        try {
            // 🔥 NUEVO: Usar servicio local en lugar de externo
            $pdfService = app(\App\Services\PdfGeneratorService::class);
            $pdf_content = $pdfService->generateFromUrl($url, $pdfParams);

            if (empty($pdf_content)) {
                throw new \Exception("PDF content is empty");
            }

            if (\Storage::disk()->put("$destination_path/$inscription->pdf_name", $pdf_content)) {
                $inscription->pdf = "$destination_path/$inscription->pdf_name";
                $inscription->save();
            }
        } catch (\Exception $e) {
            \Log::error("Failed to generate inscription PDF", [
                'inscription_id' => $inscription->id,
                'error' => $e->getMessage(),
                'url' => $url
            ]);

            if (app()->environment() === 'production') {
                throw $e;
            }
        }

        return $inscription->pdf;
    }

    private function storePacksPdf(Cart $cart)
    {
        // ✅ FIX: Usar el método de relación () y deshabilitar BrandScope
        $groupPacks = $cart->groupPacks()
            ->withoutGlobalScope(\App\Scopes\BrandScope::class)
            ->get();

        foreach ($groupPacks as $group_pack) {
            $this->storePackPdf($group_pack);
        }
    }

    private function storePackPdf(GroupPack $pack)
    {
        // ✅ FIX: Deshabilitar BrandScope en las inscripciones del pack
        $inscriptions = $pack->inscriptions()
            ->withoutGlobalScope(\App\Scopes\BrandScope::class)
            ->get();

        $pdfs = [];
        foreach ($inscriptions as $inscription) {
            if ($inscription) {
                $pdfs[] = $this->storeInscriptionPdf($inscription);
            }
        }

        if (empty($pdfs)) {
            \Log::warning('No PDFs to merge for pack', ['pack_id' => $pack->id]);
            return;
        }

        $tmp_file = $this->mergePdfs($pdfs);

        $destination_path = 'pdf/packs';

        if (\Storage::disk()->put("$destination_path/$pack->pdf_name", fopen($tmp_file, 'r'))) {
            $pack->pdf = "app/public/$destination_path/$pack->pdf_name";
            $pack->save();
        }
        unlink($tmp_file);
    }

    private function storeGiftCardsPdf(Cart $cart)
    {
        // ✅ FIX: Usar el método de relación () y deshabilitar BrandScope  
        $giftCards = $cart->gift_cards()
            ->withoutGlobalScope(\App\Scopes\BrandScope::class)
            ->get();

        foreach ($giftCards as $gift_card) {
            $this->storeGiftCardPdf($gift_card);
        }
    }

    private function storeGiftCardPdf(GiftCard $gift)
    {
        if (!$gift->code) {
            $gift->generateCode();
        }

        // Crear directorio si no existe
        $destination_path = 'pdf/gift_cards';
        $fullDirectoryPath = storage_path('app/' . $destination_path);
        if (!is_dir($fullDirectoryPath)) {
            mkdir($fullDirectoryPath, 0775, true);
            chmod($fullDirectoryPath, 0775);
        }

        $filePath = "$destination_path/{$gift->pdf_name}";

        // 🔥 NUEVO: Generar URL interna
        $url = url(route('open.gift_card.pdf', [
            'gift' => $gift->id,
            'token' => $gift->cart->token,
            'brand_code' => $gift->cart->brand->code_name,
        ]));

        // Opciones específicas para gift cards
        $options = [
            'ph' => 140,
            'pw' => 240,
            'mb' => 0,
            'mt' => 5,
            'ml' => 5,
            'mr' => 5,
        ];

        try {
            if (!\Storage::disk()->exists($filePath)) {
                if (!\Storage::disk()->exists($destination_path)) {
                    \Storage::disk()->makeDirectory($destination_path);
                }

                // 🔥 NUEVO: Usar servicio local en lugar de externo
                $pdfService = app(\App\Services\PdfGeneratorService::class);
                $pdf_content = $pdfService->generateFromUrl($url, $options);

                if (empty($pdf_content)) {
                    throw new \Exception("PDF content is empty");
                }

                if (\Storage::disk()->put($filePath, $pdf_content)) {
                    $gift->pdf = $filePath;
                    $gift->save();
                }
            }
        } catch (\Exception $e) {
            \Log::error("Gift card PDF generation failed", [
                'gift_id' => $gift->id,
                'error' => $e->getMessage(),
                'url' => $url
            ]);

            if (app()->environment('production')) {
                throw $e;
            }
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

    /**
     * Manejar fallos del job
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('CartConfirm failed', [
            'cart_id' => $this->cart->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Notificar al admin o al cliente si es necesario
        // Notification::send($admins, new JobFailedNotification($this->cart));
    }
}
