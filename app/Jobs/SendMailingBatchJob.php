<?php

namespace App\Jobs;

use App\Mail\MailingMail;
use App\Models\Mailing;
use App\Models\MailingBatch;
use App\Services\MailerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Scopes\BrandScope;
use Illuminate\Bus\Batchable;

class SendMailingBatchJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;
    public $timeout = 600;
    public $backoff = [30, 60, 120];

    protected $mailingId;
    protected $brandId;
    protected $recipients;
    protected $batchNumber;

    public function __construct(Mailing $mailing, array $recipients, int $batchNumber)
    {
        $this->mailingId = $mailing->id;
        $this->brandId = $mailing->brand_id;
        $this->recipients = $recipients;
        $this->batchNumber = $batchNumber;
    }

    public function handle(MailerService $mailerService): void
    {
        // Cargar configuración de la brand
        $this->loadBrandContext();

        // Recuperar el mailing con el contexto correcto
        $mailing = Mailing::withoutGlobalScope(BrandScope::class)
            ->where('id', $this->mailingId)
            ->where('brand_id', $this->brandId)
            ->with('brand')
            ->firstOrFail();

        // Registrar el batch (con brand_id automático por SetsBrandOnCreate)
        $batch = MailingBatch::create([
            'mailing_id' => $mailing->id,
            'brand_id' => $mailing->brand_id, // Explícito para mayor seguridad
            'batch_number' => $this->batchNumber,
            'recipients' => $this->recipients,
            'status' => 'processing',
            'started_at' => now()
        ]);

        try {
            // Configurar el mailer para la brand específica
            $mailer = $mailerService->getMailerForBrand($mailing->brand);

            // Obtener dirección noreply de la brand o usar default
            $noreplyEmail = brand_setting('mail.noreply_address', 'noreply@example.com');

            // Enviar el email con BCC
            $mailer->to($noreplyEmail)
                ->send(new MailingMail($mailing, false, $this->recipients));

            // Actualizar batch como exitoso
            $batch->update([
                'status' => 'sent',
                'sent_at' => now(),
                'completed_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('[SendMailingBatchJob] Error enviando batch', [
                'mailing_id' => $mailing->id,
                'brand_id' => $mailing->brand_id,
                'batch_number' => $this->batchNumber,
                'error' => $e->getMessage()
            ]);

            $batch->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now()
            ]);

            throw $e;
        }
    }

    protected function loadBrandContext(): void
    {
        $brand = \App\Models\Brand::find($this->brandId);

        if ($brand) {
            // Cargar configuración de la brand para este job
            app(\App\Http\Middleware\CheckBrandHost::class)
                ->loadBrandConfig($brand->code_name);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[SendMailingBatchJob] Batch falló después de reintentos', [
            'mailing_id' => $this->mailingId,
            'brand_id' => $this->brandId,
            'batch_number' => $this->batchNumber,
            'error' => $exception->getMessage()
        ]);
    }
}
