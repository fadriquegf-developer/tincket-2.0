<?php

namespace App\Services;

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

        // ✅ SIN CACHE - Siempre lee fresco
        $settings = $this->buildSettingsArray($brand);

        // 4. Accede a la key solicitada en formato "dot notation"
        return Arr::get($settings, $key, $default);
    }

    /**
     * Fusiona config global + override de brand + settings de BD
     */
    protected function buildSettingsArray($brand)
    {
        $result = [];

        // Configs que NO deben cachearse porque contienen closures u objetos no serializables
        $excludedConfigs = ['elfinder', 'cache', 'queue', 'broadcasting', 'logging'];

        // A. Config global (recorre todos los archivos de /config/)
        $configPath = config_path();
        foreach (File::files($configPath) as $file) {
            $name = basename($file, '.php');

            // Excluir configs problemáticos
            if (in_array($name, $excludedConfigs)) {
                continue;
            }

            $result[$name] = config($name);
        }

        // B. Config override de brand
        $brandConfigPath = config_path("brand/{$brand->code_name}");
        if (File::isDirectory($brandConfigPath)) {
            foreach (File::files($brandConfigPath) as $file) {
                $name = basename($file, '.php');

                // Excluir configs problemáticos
                if (in_array($name, $excludedConfigs)) {
                    continue;
                }

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
            Arr::set($result, $setting->key, $setting->value);
        }

        return $result;
    }

    /**
     * Limpia la caché de un brand (ya no hace nada, pero se mantiene por compatibilidad)
     */
    public function clearCache($brand)
    {
        // No-op: ya no hay cache para limpiar
    }
}
