<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class RedisStatusCommand extends Command
{
    protected $signature = 'redis:status 
                            {--session= : Check specific session}
                            {--clear : Clear all slot caches}';

    protected $description = 'Check Redis cache status for slots';

    public function handle()
    {
        $redis = Redis::connection();

        // Información general
        $this->info('📊 Redis Status:');
        $this->line('');

        // Estadísticas generales
        $info = $redis->info();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Used Memory', $this->formatBytes($info['used_memory'] ?? 0)],
                ['Connected Clients', $info['connected_clients'] ?? 0],
                ['Total Keys', $redis->dbsize()],
            ]
        );

        // Si se especifica una sesión
        if ($sessionId = $this->option('session')) {
            $this->checkSession($sessionId);
        } else {
            $this->checkAllSessions();
        }

        // Opción de limpiar
        if ($this->option('clear')) {
            if ($this->confirm('Are you sure you want to clear all slot caches?')) {
                $this->clearCaches();
            }
        }
    }

    private function checkSession($sessionId)
    {
        $this->line('');
        $this->info("📍 Session {$sessionId} Details:");

        $pattern = "laravel_cache:session:{$sessionId}*";
        $keys = Redis::connection()->keys($pattern);

        if (empty($keys)) {
            $this->warn("No cache entries found for session {$sessionId}");
            return;
        }

        $this->line("Found " . count($keys) . " keys:");

        foreach ($keys as $key) {
            $ttl = Redis::connection()->ttl($key);
            $type = $this->getKeyType($key);

            $this->line(sprintf(
                "  • %s (TTL: %s)",
                $type,
                $ttl > 0 ? "{$ttl}s" : 'No expiry'
            ));
        }
    }

    private function checkAllSessions()
    {
        $this->line('');
        $this->info('🎫 All Sessions in Cache:');

        $pattern = "laravel_cache:session:*";
        $keys = Redis::connection()->keys($pattern);

        if (empty($keys)) {
            $this->warn("No session caches found");
            return;
        }

        // Agrupar por sesión
        $sessions = [];
        foreach ($keys as $key) {
            if (preg_match('/session:(\d+)/', $key, $matches)) {
                $sessionId = $matches[1];
                if (!isset($sessions[$sessionId])) {
                    $sessions[$sessionId] = 0;
                }
                $sessions[$sessionId]++;
            }
        }

        $rows = [];
        foreach ($sessions as $sessionId => $count) {
            $rows[] = ["Session {$sessionId}", "{$count} keys"];
        }

        $this->table(['Session', 'Cache Entries'], $rows);
    }

    private function clearCaches()
    {
        $pattern = "laravel_cache:session:*";
        $keys = Redis::connection()->keys($pattern);

        if (empty($keys)) {
            $this->info("No caches to clear");
            return;
        }

        $count = 0;
        foreach ($keys as $key) {
            Redis::connection()->del($key);
            $count++;
        }

        $this->info("✅ Cleared {$count} cache entries");
    }

    private function getKeyType($key)
    {
        if (strpos($key, ':config:') !== false) {
            return 'Configuration';
        }
        if (strpos($key, ':slot:') !== false) {
            return 'Slot State';
        }
        if (strpos($key, ':avail:') !== false) {
            return 'Availability';
        }
        if (strpos($key, ':bulk:') !== false) {
            return 'Bulk Availability';
        }
        if (strpos($key, ':rates:') !== false) {
            return 'Rates';
        }

        return 'Unknown';
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
