<?php

namespace App\Jobs;

use App\Models\Session;
use App\Services\RedisSlotsService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class UpdateSessionSlotCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;
    public $maxExceptions = 2;

    protected $session;

    /**
     * Create a new job instance.
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
        // Configurar el queue aquí en el constructor
        $this->onQueue('critical');
    }

    /**
     * ID único para evitar duplicados
     */
    public function uniqueId(): string
    {
        return 'session-cache-' . $this->session->id;
    }

    /**
     * Tiempo que debe esperar antes de permitir otro job igual
     */
    public function uniqueFor(): int
    {
        return 60; // 60 segundos
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        if (!$this->session->is_numbered) {
            return;
        }

        try {
            $service = new RedisSlotsService($this->session);

            // Llamar a regenerateCache directamente (no retorna success)
            $service->regenerateCache();
        } catch (\Exception $e) {
            Log::error("Error updating Redis cache for session {$this->session->id}: " . $e->getMessage());
            throw $e; // Re-throw para que el job se reintente
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Job failed for session {$this->session->id}: " . $exception->getMessage());

        // Opcional: Notificar a administradores o registrar en una tabla de fallos
        // Notification::send($admins, new JobFailedNotification($this->session, $exception));
    }
}
