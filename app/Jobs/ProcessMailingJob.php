<?php

namespace App\Jobs;

use App\Models\Mailing;
use App\Scopes\BrandScope;
use App\Services\MailingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\Middleware\RateLimited;

class ProcessMailingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 3;
    public $maxExceptions = 1;
    public $backoff = [60, 300, 900];

    protected $mailingId;
    protected $brandId;

    public function __construct(Mailing $mailing)
    {
        // Guardar solo IDs para evitar problemas de serialización con scopes
        $this->mailingId = $mailing->id;
        $this->brandId = $mailing->brand_id;
    }

    /**
     * Middleware del job para evitar procesamiento concurrente
     * y limitar rate de envío
     */
    public function middleware(): array
    {
        return [
            // Evitar que el mismo mailing se procese múltiples veces
            new WithoutOverlapping($this->mailingId)
                ->dontRelease()
                ->expireAfter(3600),

            // Rate limiting por brand (opcional)
            new RateLimited('mailings-brand-' . $this->brandId),
        ];
    }

    /**
     * Determinar si debe liberarse en caso de overlap
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(2);
    }

    public function handle(MailingService $mailingService): void
    {

        $this->loadBrandContext();

        $mailing = Mailing::withoutGlobalScope(BrandScope::class)
            ->where('id', $this->mailingId)
            ->where('brand_id', $this->brandId)
            ->firstOrFail();

        try {
            $mailingService->process($mailing);
        } catch (\Exception $e) {
            \Log::error('[ProcessMailingJob] Error', [
                'mailing_id' => $this->mailingId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    protected function loadBrandContext(): void
    {
        // Simular el contexto de brand para este job
        $brand = \App\Models\Brand::find($this->brandId);

        if ($brand) {
            // Cargar la configuración de la brand
            app(\App\Http\Middleware\CheckBrandHost::class)
                ->loadBrandConfig($brand->code_name);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[ProcessMailingJob] Job falló después de todos los reintentos', [
            'mailing_id' => $this->mailingId,
            'brand_id' => $this->brandId,
            'error' => $exception->getMessage()
        ]);

        Mailing::withoutGlobalScope(BrandScope::class)
            ->where('id', $this->mailingId)
            ->where('brand_id', $this->brandId)
            ->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'failed_at' => now()
            ]);
    }
}
