<?php

namespace App\Services;

use App\Jobs\SendMailingBatchJob;
use App\Models\Mailing;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MailingService
{
    const BATCH_SIZE = 500;
    const DELAY_BETWEEN_BATCHES = 30;

    public function process(Mailing $mailing): void
    {
        // Validar que el mailing pertenece a la brand actual
        $this->validateBrandContext($mailing);

        // ✅ Verificar que está en estado processing (ya cambiado por el controller)
        if ($mailing->status !== 'processing') {
            // Si no está en processing, verificar que sea válido procesarlo
            if ($mailing->status === 'sent') {
                throw new \Exception('Este mailing ya fue enviado');
            }

            if ($mailing->status !== 'draft') {
                throw new \Exception('Estado inválido para procesar: ' . $mailing->status);
            }

            // Si llegamos aquí y está en draft, cambiarlo a processing
            // (esto es un fallback por si el controller no lo cambió)
            $mailing->update([
                'status' => 'processing',
                'processing_started_at' => $mailing->processing_started_at ?? now()
            ]);
        }

        $recipients = $this->parseAndValidateRecipients($mailing->emails);

        if (empty($recipients)) {
            // Si no hay destinatarios, marcar como fallido
            $mailing->update([
                'status' => 'failed',
                'error_message' => 'No hay destinatarios válidos',
                'failed_at' => now(),
                'processing_completed_at' => now()
            ]);

            throw new \Exception('No hay destinatarios válidos');
        }

        // Actualizar total de destinatarios (el estado ya está en processing)
        $mailing->update([
            'total_recipients' => count($recipients),
            // No cambiar status aquí porque ya está en processing
        ]);

        try {
            $this->dispatchBatches($mailing, $recipients);
        } catch (\Exception $e) {
            // Si falla el dispatch, marcar como fallido
            $mailing->update([
                'status' => 'failed',
                'error_message' => 'Error al crear batches: ' . $e->getMessage(),
                'failed_at' => now(),
                'processing_completed_at' => now()
            ]);

            throw $e;
        }
    }

    protected function validateBrandContext(Mailing $mailing): void
    {
        $currentBrand = get_current_brand();

        if ($currentBrand && $mailing->brand_id !== $currentBrand->id) {
            throw new \Exception('No tienes permiso para procesar mailings de otra brand');
        }
    }

    protected function parseAndValidateRecipients(string $emails): array
    {
        $emails = str_replace(["\r", "\n", ";", "'", '"'], ',', $emails);

        return collect(explode(',', $emails))
            ->map(fn($email) => trim($email))
            ->filter(fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->toArray();
    }

    protected function dispatchBatches(Mailing $mailing, array $recipients): void
    {
        $chunks = array_chunk($recipients, self::BATCH_SIZE);
        $jobs = [];
        $delay = 0;

        foreach ($chunks as $index => $chunk) {
            $batchNumber = $index + 1;

            $job = new SendMailingBatchJob($mailing, $chunk, $batchNumber);

            if ($delay > 0) {
                $job->delay(now()->addSeconds($delay));
            }

            $jobs[] = $job;
            $delay += self::DELAY_BETWEEN_BATCHES;
        }

        // Usar Bus::batch con nombre único por brand
        Bus::batch($jobs)
            ->name("mailing-{$mailing->brand_id}-{$mailing->id}")
            ->onQueue('mailings')
            ->allowFailures()
            ->finally(function () use ($mailing) {
                $this->finalizeMailing($mailing);
            })
            ->dispatch();
    }

    protected function finalizeMailing(Mailing $mailing): void
    {
        // Recargar con relaciones, respetando el scope de brand
        $mailing->load('batches');

        $allSent = $mailing->batches->every(fn($batch) => $batch->status === 'sent');
        $totalBatches = $mailing->batches->count();
        $sentBatches = $mailing->batches->where('status', 'sent')->count();
        $failedBatches = $mailing->batches->where('status', 'failed')->count();

        // Determinar el estado final
        $finalStatus = 'draft';
        if ($totalBatches > 0) {
            if ($allSent) {
                $finalStatus = 'sent';
            } elseif ($sentBatches === 0) {
                $finalStatus = 'failed';
            } else {
                $finalStatus = 'partial';
            }
        }

        $mailing->update([
            'sent_at' => $allSent ? now() : null,
            'processing_completed_at' => now(),
            'status' => $finalStatus,
            'batches_sent' => $sentBatches,
            'batches_failed' => $failedBatches,
        ]);
    }
}
