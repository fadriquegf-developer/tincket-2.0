<?php

namespace App\Observers;

use App\Models\Brand;
use Illuminate\Support\Facades\Cache;

class BrandObserver
{
    /**
     * Handle the Brand "saved" event (created o updated).
     */
    public function saved(Brand $brand): void
    {
        $this->clearBrandCache($brand);
    }

    /**
     * Handle the Brand "deleted" event.
     */
    public function deleted(Brand $brand): void
    {
        $this->clearBrandCache($brand);
    }

    /**
     * Handle the Brand "restored" event.
     */
    public function restored(Brand $brand): void
    {
        $this->clearBrandCache($brand);
    }

    /**
     * Limpiar cache relacionado con la brand
     */
    private function clearBrandCache(Brand $brand): void
    {
        // Limpiar cache del host actual
        if ($brand->allowed_host) {
            Cache::forget('brand:host:' . $brand->allowed_host);
        }

        // Si cambió el host, limpiar el antiguo también
        if ($brand->isDirty('allowed_host') && $brand->getOriginal('allowed_host')) {
            Cache::forget('brand:host:' . $brand->getOriginal('allowed_host'));
        }

        // Limpiar cache de settings de esta brand
        Cache::tags(['brand:' . $brand->id])->flush();
    }
}
