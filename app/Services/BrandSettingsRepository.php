<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use App\Models\Setting;

class BrandSettingsRepository 
{
    public function get($key, $brand = null, $default = null)
    {
        // 1. Obtén el brand actual si no se pasa
        if (!$brand) {
            $brand = get_current_brand();
        }

        if (!$brand) {
            $brand = request()->get('brand');
        }

        if (!$brand) {
            // Puedes devolver null, lanzar excepción, o usar config global si no hay brand
            return config($key, $default);
        }

        // 2. Cache key única por brand
        $cacheKey = "brand_settings_array_{$brand->id}";

        // 3. Obtén el array completo de settings fusionado y cacheado
        $settings = Cache::remember($cacheKey, 300, function () use ($brand) {
            return $this->buildSettingsArray($brand);
        });

        // 4. Accede a la key solicitada en formato "dot notation"
        return Arr::get($settings, $key, $default);
    }

    /**
     * Fusiona config global + override de brand + settings de BD
     */
    protected function buildSettingsArray($brand)
    {
        $result = [];

        // A. Config global (recorre todos los archivos de /config/)
        $configPath = config_path();
        foreach (File::files($configPath) as $file) {
            $name = basename($file, '.php');
            $result[$name] = config($name);
        }

        // B. Config override de brand
        $brandConfigPath = config_path("brand/{$brand->code_name}");
        if (File::isDirectory($brandConfigPath)) {
            foreach (File::files($brandConfigPath) as $file) {
                $name = basename($file, '.php');
                $brandArray = include $file;
                if (isset($result[$name])) {
                    $result[$name] = array_replace_recursive($result[$name], $brandArray);
                } else {
                    $result[$name] = $brandArray;
                }
            }
        }

        // C. Settings desde la BD (tabla settings)
        $dbSettings = Setting::where('brand_id', $brand->id)->get();
        foreach ($dbSettings as $setting) {
            // Las keys deben estar en notación dot, ej: "clients.email_from"
            Arr::set($result, $setting->key, $setting->value);
        }

        return $result;
    }

    /**
     * Limpia la caché de un brand (llamar tras actualizar settings en panel)
     */
    public function clearCache($brand)
    {
        $cacheKey = "brand_settings_array_{$brand->id}";
        Cache::forget($cacheKey);
    }
}
