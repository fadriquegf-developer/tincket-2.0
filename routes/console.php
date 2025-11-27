<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/**
 * Opcional: Limpiar cache antigua de Redis
 */
Schedule::call(function () {
    // Limpiar keys de Redis que tengan más de 24 horas sin usarse
    $redis = \Illuminate\Support\Facades\Redis::connection();
    $pattern = 'laravel_cache:session:*';
    $keys = $redis->keys($pattern);

    foreach ($keys as $key) {
        $ttl = $redis->ttl($key);
        // Si no tiene TTL o es muy viejo, eliminar
        if ($ttl == -1 || $ttl > 86400) {
            $redis->del($key);
        }
    }
})->daily()->at('03:00');

// Limpiar cache en memoria cada 5 minutos
Schedule::command('cache:clean-memory')
    ->everyFiveMinutes()
    ->withoutOverlapping();
