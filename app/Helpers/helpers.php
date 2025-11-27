<?php

use App\Models\Brand;
use App\Services\BrandSettingsRepository;
use Illuminate\Support\Facades\Cache;

if (!function_exists('get_current_brand')) {
    function get_current_brand()
    {
        if (app()->runningInConsole()) {
            return null;
        }

        // Cache en memoria estática para la misma request (evita múltiples queries en la misma petición)
        static $memoryCache = [];

        // Primero verificar si viene del request (contexto API)
        // Esto es importante porque en la API el brand viene del middleware
        if (request()->has('brand') && request()->get('brand')) {
            $brandFromRequest = request()->get('brand');
            // Si es un objeto Brand válido, devolverlo
            if ($brandFromRequest instanceof Brand) {
                return $brandFromRequest;
            }
        }

        // Si no hay brand en el request, buscar por host (contexto web normal)
        $host = request()->getHost();

        // Si ya lo tenemos en memoria para esta request, devolverlo
        if (isset($memoryCache[$host])) {
            return $memoryCache[$host];
        }

        // Buscar en cache de Redis/Memcached con TTL de 1 hora
        $cacheKey = 'brand:host:' . $host;

        $brand = Cache::remember($cacheKey, 3600, function () use ($host) {
            return Brand::where('allowed_host', $host)->first();
        });

        // Guardar en memoria para esta request
        $memoryCache[$host] = $brand;

        return $brand;
    }
}

if (!function_exists('get_current_brand_id')) {
    function get_current_brand_id()
    {
        $brand = get_current_brand();
        return $brand ? $brand->id : null;
    }
}

if (!function_exists('get_brand_capability')) {
    function get_brand_capability()
    {
        // Usar get_current_brand() que ya tiene cache
        $brand = get_current_brand();

        if (!$brand || !$brand->capability) {
            return null;
        }

        // La relación capability también se puede cachear si es necesario
        if (!$brand->relationLoaded('capability')) {
            $brand->load('capability');
        }

        return $brand->capability->code_name;
    }
}

if (!function_exists('brand_setting')) {
    function brand_setting($key, $default = null)
    {
        return app(BrandSettingsRepository::class)->get($key, null, $default);
    }
}

if (!function_exists('brand_asset')) {
    /**
     * Every Brand has its media repository
     */
    function brand_asset($asset, $brand = null, $https = true)
    {
        if (!$brand) {
            // Usar get_current_brand() cacheado
            $brand = get_current_brand() ?: request()->get('brand');
        }

        if ($https) {
            $https = !env('BYPASS_HTTPS', false);
        }

        return ($https ? "https://" : "http://") . $brand->allowed_host . '/' . trim($asset, '/');
    }
}

if (!function_exists('clear_brand_cache')) {
    /**
     * Limpiar cache de brand cuando se actualice
     * Llamar esto desde BrandObserver cuando se actualice una brand
     */
    function clear_brand_cache($host = null)
    {
        if ($host) {
            Cache::forget('brand:host:' . $host);
        } else {
            // Limpiar todas las brands
            $brands = Brand::all(['allowed_host']);
            foreach ($brands as $brand) {
                if ($brand->allowed_host) {
                    Cache::forget('brand:host:' . $brand->allowed_host);
                }
            }
        }
    }
}

if (!function_exists('starts_with')) {
    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    function starts_with($haystack, $needles)
    {
        return Str::startsWith($haystack, $needles);
    }
}

if (!function_exists('str_random')) {
    /**
     * Generate a more truly "random" alpha-numeric string.
     *
     * @param  int  $length
     * @return string
     *
     * @throws \RuntimeException
     */
    function str_random($length = 16)
    {
        return Str::random($length);
    }
}

if (!function_exists('ends_with')) {
    /**
     * Determine if a given string ends with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    function ends_with($haystack, $needles)
    {
        return Str::endsWith($haystack, $needles);
    }
}

if (!function_exists('replace_route_parameters')) {

    /**
     * Replace all of the wildcard parameters for a route path.
     *
     * @param  string  $path
     * @param  array  $parameters
     * @return string
     */
    function replace_route_parameters($path, array $parameters, $root = null)
    {
        $path = preg_replace_callback('/\{.*?\}/', function ($match) use (&$parameters) {
            return (empty($parameters) && !Illuminate\Support\Str::endsWith($match[0], '?}')) ? $match[0] : array_shift($parameters);
        }, $path);

        return trim($root . '/' . preg_replace('/\{.*?\?\}/', '', $path), '/');
    }
}
