<?php

namespace App\Console\Commands;

use App\Models\Session;
use App\Models\Slot;
use App\Models\Inscription;
use App\Services\RedisSlotsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class DiagnoseRedisSlots extends Command
{
    protected $signature = 'redis:diagnose-slots 
                            {session_id? : ID de la sesión a diagnosticar}
                            {--fix : Corregir inconsistencias encontradas}
                            {--clear : Limpiar completamente el cache de Redis para la sesión}';

    protected $description = 'Diagnostica y opcionalmente corrige el estado de slots en Redis';

    public function handle()
    {
        $sessionId = $this->argument('session_id');
        $fix = $this->option('fix');
        $clear = $this->option('clear');

        if ($sessionId) {
            $this->diagnoseSession($sessionId, $fix, $clear);
        } else {
            $this->diagnoseAllSessions($fix, $clear);
        }
    }

    private function diagnoseSession($sessionId, $fix = false, $clear = false)
    {
        $session = Session::find($sessionId);

        if (!$session) {
            $this->error("Sesión {$sessionId} no encontrada");
            return;
        }

        // Manejar nombre traducible
        $sessionName = is_array($session->name) ? json_encode($session->name) : $session->name;
        if (is_string($session->name) && json_decode($session->name)) {
            $nameData = json_decode($session->name, true);
            $sessionName = $nameData['es'] ?? $nameData['ca'] ?? $session->name;
        }

        $this->info("=== Diagnosticando Sesión {$sessionId}: {$sessionName} ===");

        // Si se pidió limpiar, hacerlo primero
        if ($clear) {
            $this->clearSessionRedis($session);
            return;
        }

        // Verificar si la sesión es numerada
        if (!$session->is_numbered) {
            $this->warn("⚠️ La sesión no es numerada, saltando diagnóstico de slots");
            return;
        }

        // Verificar que tiene espacio asociado
        if (!$session->space_id || !$session->space) {
            $this->warn("⚠️ La sesión no tiene espacio asociado");
            return;
        }

        // Obtener todos los slots del espacio
        $slots = Slot::where('space_id', $session->space_id)->get();
        $this->info("Total slots en espacio: {$slots->count()}");

        // Si no hay slots, no hay nada que verificar
        if ($slots->isEmpty()) {
            $this->warn("⚠️ No hay slots en el espacio de esta sesión");
            return;
        }

        // Obtener inscripciones activas
        $activeInscriptions = Inscription::where('session_id', $session->id)
            ->whereHas('cart', function ($q) {
                $q->whereNotNull('confirmation_code')
                    ->orWhere('expires_on', '>', Carbon::now());
            })
            ->get();
        $this->info("Inscripciones activas: {$activeInscriptions->count()}");

        // Analizar estado en Redis
        $redisService = new RedisSlotsService($session);
        $inconsistencies = [];

        foreach ($slots as $slot) {
            $redisAvailable = $redisService->isSlotAvailable($slot->id);
            $dbOccupied = $activeInscriptions->where('slot_id', $slot->id)->isNotEmpty();

            if ($redisAvailable && $dbOccupied) {
                $inconsistencies[] = [
                    'slot_id' => $slot->id,
                    'code' => $slot->code,
                    'issue' => 'Redis dice DISPONIBLE pero está OCUPADO en DB'
                ];
            } elseif (!$redisAvailable && !$dbOccupied) {
                $inconsistencies[] = [
                    'slot_id' => $slot->id,
                    'code' => $slot->code,
                    'issue' => 'Redis dice OCUPADO pero está DISPONIBLE en DB'
                ];
            }
        }

        $inconsistencyCount = count($inconsistencies);

        if ($inconsistencyCount === 0) {
            $this->info("✅ No se encontraron inconsistencias");
        } else {
            $this->warn("⚠️ Se encontraron " . $inconsistencyCount . " inconsistencias:");

            foreach ($inconsistencies as $issue) {
                $this->line("  - Slot {$issue['slot_id']} ({$issue['code']}): {$issue['issue']}");
            }

            if ($fix) {
                $this->fixInconsistencies($session, $inconsistencies, $activeInscriptions);
            } else {
                $this->info("\nUsa --fix para corregir estas inconsistencias");
            }
        }

        // Mostrar estadísticas de Redis
        $this->showRedisStats($session);
    }

    private function clearSessionRedis(Session $session)
    {
        $this->warn("Limpiando cache de Redis para sesión {$session->id}...");

        $redisService = new RedisSlotsService($session);

        // Limpiar todas las keys relacionadas
        $brandPrefix = $session->brand_id ? "b{$session->brand_id}" : 'default';
        $patterns = [
            "{$brandPrefix}:slots:s{$session->id}:*",
            "{$brandPrefix}:free:s{$session->id}",
            "{$brandPrefix}:available_web:s{$session->id}",
            "{$brandPrefix}:blocked:s{$session->id}",
            "session_{$session->id}_*",
        ];

        $deletedCount = 0;
        foreach ($patterns as $pattern) {
            $keys = Redis::keys($pattern);
            foreach ($keys as $key) {
                Redis::del($key);
                $deletedCount++;
            }
        }

        $this->info("✅ Eliminadas {$deletedCount} keys de Redis");

        // Regenerar cache
        $this->info("Regenerando cache...");
        $redisService->regenerateCache();
        $this->info("✅ Cache regenerado");
    }

    private function fixInconsistencies(Session $session, array $inconsistencies, $activeInscriptions)
    {
        $this->info("\n🔧 Corrigiendo inconsistencias...");

        $redisService = new RedisSlotsService($session);
        $fixed = 0;

        foreach ($inconsistencies as $issue) {
            $slotId = $issue['slot_id'];
            $isOccupiedInDb = $activeInscriptions->where('slot_id', $slotId)->isNotEmpty();

            if ($isOccupiedInDb) {
                // Slot ocupado en DB, marcar como ocupado en Redis
                $inscription = $activeInscriptions->where('slot_id', $slotId)->first();
                $redisService->lockSlot($slotId, 2, $inscription->id, $inscription->cart_id);
                $this->line("  ✓ Marcado slot {$slotId} como OCUPADO en Redis");
            } else {
                // Slot libre en DB, marcar como disponible en Redis
                $redisService->freeSlot($slotId);
                $this->line("  ✓ Marcado slot {$slotId} como DISPONIBLE en Redis");
            }
            $fixed++;
        }

        // Invalidar cache
        $redisService->invalidateAvailabilityCache();

        $this->info("✅ Corregidas {$fixed} inconsistencias");
    }

    private function showRedisStats(Session $session)
    {
        $this->info("\n=== Estadísticas de Redis ===");

        $brandPrefix = $session->brand_id ? "b{$session->brand_id}" : 'default';

        // Contar keys
        $slotKeys = count(Redis::keys("{$brandPrefix}:slots:s{$session->id}:*"));
        $this->line("Keys de slots: {$slotKeys}");

        // Verificar cache de disponibilidad
        $freeKey = "{$brandPrefix}:free:s{$session->id}";
        $availableWebKey = "{$brandPrefix}:available_web:s{$session->id}";

        if (Redis::exists($freeKey)) {
            $freeCount = Redis::get($freeKey);
            $this->line("Slots libres en cache: {$freeCount}");
        } else {
            $this->line("Cache de slots libres: NO EXISTE");
        }

        if (Redis::exists($availableWebKey)) {
            $availableCount = Redis::get($availableWebKey);
            $this->line("Slots disponibles web: {$availableCount}");
        } else {
            $this->line("Cache de disponibles web: NO EXISTE");
        }

        // Memoria usada (aproximado)
        $memoryInfo = Redis::info('memory');
        if (isset($memoryInfo['used_memory_human'])) {
            $this->line("Memoria Redis usada: {$memoryInfo['used_memory_human']}");
        }
    }

    private function diagnoseAllSessions($fix = false, $clear = false)
    {
        $sessions = Session::where('is_numbered', 1)
            ->where('ends_on', '>', Carbon::now())
            ->get();

        $this->info("Analizando {$sessions->count()} sesiones numeradas activas...\n");

        foreach ($sessions as $session) {
            $this->diagnoseSession($session->id, $fix, $clear);
            $this->line("");
        }
    }
}
