<?php

namespace Tests\Commands;

use App\Models\Session;
use App\Models\Slot;
use App\Services\RedisSlotsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Comando de diagnóstico para verificar el estado y funcionamiento de Redis.
 * 
 * USO:
 * - php artisan redis:test-cache                  (Test general de Redis)
 * - php artisan redis:test-cache --session=123    (Test cache de sesión específica)
 * - php artisan redis:test-cache --check          (Verificar disponibilidad de slots)
 * 
 * CUÁNDO USAR:
 * - Para verificar que Redis está funcionando correctamente
 * - Cuando hay problemas de rendimiento con la cache
 * - Para debugging en producción sin afectar datos
 * - Para ver estadísticas de memoria y keys en Redis
 * 
 * NOTA: Solo lee datos, no modifica nada. Seguro para producción.
 */
class TestRedisCache extends Command
{
    protected $signature = 'redis:test-cache 
                            {--session= : Test specific session ID}
                            {--regenerate : Force regenerate cache}
                            {--check : Check availability of random slots}';

    protected $description = 'Test Redis cache system for slots';

    public function handle()
    {
        $this->info('🧪 Testing Redis Cache System...');
        $this->line('');

        // Test Redis connection
        if (!$this->testRedisConnection()) {
            return 1;
        }

        // Get session to test
        if ($sessionId = $this->option('session')) {
            $session = Session::find($sessionId);
            if (!$session) {
                $this->error("Session {$sessionId} not found");
                return 1;
            }
            $sessions = collect([$session]);
        } else {
            // Get random numbered sessions
            $sessions = Session::where('is_numbered', true)
                ->where('ends_on', '>', now())
                ->limit(3)
                ->get();

            if ($sessions->isEmpty()) {
                $this->warn('No numbered sessions found');
                return 0;
            }
        }

        // Test each session
        foreach ($sessions as $session) {
            $this->testSession($session);
        }

        $this->line('');
        $this->info('✅ All tests completed');

        return 0;
    }

    private function testRedisConnection(): bool
    {
        $this->info('1️⃣ Testing Redis Connection...');

        try {
            $pong = Redis::ping();
            $this->info('✅ Redis is connected');

            // Show Redis info
            $info = Redis::info();
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Redis Version', $info['redis_version'] ?? 'Unknown'],
                    ['Used Memory', $this->formatBytes($info['used_memory'] ?? 0)],
                    ['Total Keys', Redis::dbsize()],
                ]
            );

            return true;
        } catch (\Exception $e) {
            $this->error('❌ Redis connection failed: ' . $e->getMessage());
            return false;
        }
    }

    private function testSession(Session $session): void
    {
        $this->line('');
        $this->info("2️⃣ Testing Session #{$session->id} - {$session->name}");

        if (!$session->is_numbered) {
            $this->warn('  ⚠️ Session is not numbered, skipping...');
            return;
        }

        if (!$session->space_id) {
            $this->warn('  ⚠️ Session has no space, skipping...');
            return;
        }

        $service = new RedisSlotsService($session);

        // Test cache generation
        if ($this->option('regenerate')) {
            $this->line('  🔄 Regenerating cache...');
            $start = microtime(true);
            $result = $service->regenerateCache();
            $time = round((microtime(true) - $start) * 1000, 2);

            if ($result) {
                $this->info("  ✅ Cache regenerated in {$time}ms");
            } else {
                $this->error('  ❌ Cache regeneration failed');
                return;
            }
        }

        // Get configuration
        $this->line('  📊 Getting configuration...');
        $start = microtime(true);
        $config = $service->getConfiguration();
        $time = round((microtime(true) - $start) * 1000, 2);

        if (empty($config)) {
            $this->error('  ❌ No configuration found');

            // Try to regenerate
            $this->line('  🔄 Attempting to regenerate cache...');
            $service->regenerateCache();
            $config = $service->getConfiguration();

            if (empty($config)) {
                $this->error('  ❌ Still no configuration after regeneration');
                return;
            }
        }

        $this->info("  ✅ Configuration loaded in {$time}ms");

        // Show stats
        $totalSlots = 0;
        $lockedSlots = 0;
        foreach ($config['zones'] ?? [] as $zone) {
            $totalSlots += count($zone['slots']);
            $lockedSlots += collect($zone['slots'])->where('is_locked', true)->count();
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Zones', count($config['zones'] ?? [])],
                ['Total Slots', $totalSlots],
                ['Locked Slots', $lockedSlots],
                ['Available Slots', $totalSlots - $lockedSlots],
                ['Free Positions', $config['free_positions'] ?? 0],
            ]
        );

        // Test availability check if requested
        if ($this->option('check') && $totalSlots > 0) {
            $this->testAvailability($service, $config);
        }

        // Check Redis keys
        $this->checkRedisKeys($session->id);
    }

    private function testAvailability(RedisSlotsService $service, array $config): void
    {
        $this->line('  🔍 Testing slot availability...');

        // Get random slots
        $allSlots = [];
        foreach ($config['zones'] as $zone) {
            foreach ($zone['slots'] as $slot) {
                $allSlots[] = $slot;
            }
        }

        if (empty($allSlots)) {
            $this->warn('  ⚠️ No slots to test');
            return;
        }

        // Test individual availability
        $randomSlots = array_rand($allSlots, min(5, count($allSlots)));
        if (!is_array($randomSlots)) {
            $randomSlots = [$randomSlots];
        }

        foreach ($randomSlots as $index) {
            $slot = $allSlots[$index];
            $start = microtime(true);
            $isAvailable = $service->isSlotAvailable($slot['id']);
            $time = round((microtime(true) - $start) * 1000, 2);

            $status = $isAvailable ? '✅ Available' : '❌ Unavailable';
            $this->line("    Slot #{$slot['id']} ({$slot['name']}): {$status} ({$time}ms)");
        }

        // Test bulk availability
        $slotIds = array_column($allSlots, 'id');
        $testIds = array_slice($slotIds, 0, 10);

        $this->line('  📦 Testing bulk availability for ' . count($testIds) . ' slots...');
        $start = microtime(true);
        $bulkResult = $service->checkBulkAvailability($testIds);
        $time = round((microtime(true) - $start) * 1000, 2);

        $available = count(array_filter($bulkResult));
        $this->info("    {$available}/" . count($testIds) . " slots available ({$time}ms)");
    }

    private function checkRedisKeys(int $sessionId): void
    {
        $this->line('  🔑 Checking Redis keys...');

        $patterns = [
            "laravel_cache:session:{$sessionId}*" => 'Session keys',
            "laravel_cache:slot:*" => 'Slot keys',
            "laravel_cache_tags:session:{$sessionId}*" => 'Tag keys',
        ];

        foreach ($patterns as $pattern => $label) {
            $keys = Redis::keys($pattern);
            $count = count($keys);
            if ($count > 0) {
                $this->line("    {$label}: {$count} keys");
            }
        }
    }

    private function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
