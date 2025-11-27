<?php

namespace App\Console\Commands;

use App\Services\RedisSlotsService;
use Illuminate\Console\Command;

class CleanMemoryCacheCommand extends Command
{
    protected $signature = 'cache:clean-memory';
    protected $description = 'Clean expired entries from in-memory cache';

    public function handle()
    {
        $this->info('Cleaning expired memory cache entries...');

        // Obtener estadísticas antes
        $service = new RedisSlotsService(new \App\Models\Session(['id' => 1]));
        $statsBefore = $service->getMemoryCacheStats();
        $this->info('Before: ' . json_encode($statsBefore));

        // Limpiar entradas de más de 2 minutos
        $expired = RedisSlotsService::cleanExpiredMemCache(120);

        // Estadísticas después
        $statsAfter = $service->getMemoryCacheStats();
        $this->info('After: ' . json_encode($statsAfter));

        $this->info("Cleaned {$expired} expired entries");

        return Command::SUCCESS;
    }
}
