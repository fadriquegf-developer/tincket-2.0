<?php

namespace Tests\Commands;

use App\Models\Session;
use App\Services\RedisSlotsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Comando para poblar/regenerar la cache de Redis con datos de sesiones numeradas.
 * 
 * USO:
 * - php artisan redis:populate --all              (Regenera TODAS las sesiones activas)
 * - php artisan redis:populate --session=123      (Regenera una sesión específica)
 * - php artisan redis:populate --batch=20         (Procesa en lotes de 20)
 * 
 * CUÁNDO USAR:
 * - Después de un deployment en producción
 * - Cuando se corrompe la cache de Redis
 * - Para precalentar cache antes de un evento importante
 * - Si se han modificado masivamente los datos de sesiones
 * 
 * NOTA: Este proceso puede tardar varios minutos con muchas sesiones.
 */
class PopulateRedisCache extends Command
{
    protected $signature = 'redis:populate 
                            {--all : Regenerate cache for all active sessions}
                            {--session= : Regenerate specific session}
                            {--batch=10 : Number of sessions per batch}';

    protected $description = 'Populate Redis cache with existing sessions';

    public function handle()
    {
        $this->info('🚀 Populating Redis Cache...');

        if ($sessionId = $this->option('session')) {
            return $this->regenerateSession($sessionId);
        }

        if ($this->option('all')) {
            return $this->regenerateAllSessions();
        }

        // Default: regenerate only upcoming sessions
        return $this->regenerateUpcomingSessions();
    }

    private function regenerateSession($sessionId): int
    {
        $session = Session::find($sessionId);

        if (!$session) {
            $this->error("Session {$sessionId} not found");
            return 1;
        }

        if (!$session->is_numbered) {
            $this->warn("Session {$sessionId} is not numbered");
            return 0;
        }

        $this->info("Regenerating cache for session {$sessionId}...");

        $service = new RedisSlotsService($session);
        $success = $service->regenerateCache();

        if ($success) {
            $this->info("✅ Session {$sessionId} cache regenerated");

            // Show stats
            $config = $service->getConfiguration();
            $totalSlots = 0;
            foreach ($config['zones'] ?? [] as $zone) {
                $totalSlots += count($zone['slots']);
            }

            $this->line("   Zones: " . count($config['zones'] ?? []));
            $this->line("   Slots: {$totalSlots}");
            $this->line("   Free positions: " . ($config['free_positions'] ?? 0));
        } else {
            $this->error("❌ Failed to regenerate cache for session {$sessionId}");
            return 1;
        }

        return 0;
    }

    private function regenerateAllSessions(): int
    {
        $batchSize = (int) $this->option('batch');

        $sessions = Session::where('is_numbered', true)
            ->whereNotNull('space_id')
            ->get();

        return $this->processSessions($sessions, $batchSize);
    }

    private function regenerateUpcomingSessions(): int
    {
        $batchSize = (int) $this->option('batch');

        $sessions = Session::where('is_numbered', true)
            ->whereNotNull('space_id')
            ->where('ends_on', '>', now())
            ->where('inscription_starts_on', '<', now()->addDays(30))
            ->get();

        if ($sessions->isEmpty()) {
            $this->warn('No upcoming numbered sessions found');
            return 0;
        }

        $this->info("Found {$sessions->count()} upcoming sessions to process");

        return $this->processSessions($sessions, $batchSize);
    }

    private function processSessions($sessions, int $batchSize): int
    {
        $progressBar = $this->output->createProgressBar($sessions->count());
        $progressBar->start();

        $successful = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($sessions->chunk($batchSize) as $batch) {
            foreach ($batch as $session) {
                if (!$session->space) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                try {
                    $service = new RedisSlotsService($session);
                    if ($service->regenerateCache()) {
                        $successful++;
                    } else {
                        $failed++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    \Log::error("Failed to regenerate cache for session {$session->id}: " . $e->getMessage());
                }

                $progressBar->advance();
            }

            // Small delay between batches to avoid overload
            usleep(100000); // 100ms
        }

        $progressBar->finish();
        $this->newLine(2);

        // Show summary
        $this->info('📊 Summary:');
        $this->line("   ✅ Successful: {$successful}");
        if ($failed > 0) {
            $this->line("   ❌ Failed: {$failed}");
        }
        if ($skipped > 0) {
            $this->line("   ⏭️ Skipped (no space): {$skipped}");
        }

        return $failed > 0 ? 1 : 0;
    }
}
