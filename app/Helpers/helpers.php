<?php

use App\Models\Brand;
use App\Services\BrandSettingsRepository;

if (!function_exists('get_current_brand')) {
    function get_current_brand()
    {
        if (app()->runningInConsole()) {
            // Indica que no hay brand en consola
            return null;
        }

        $host = request()->getHost();
        return Brand::where('allowed_host', $host)->first();
    }
}

if (!function_exists('get_brand_capability')) {
    function get_brand_capability()
    {
        $host = request()->getHost();
        $brand = Brand::where('allowed_host', $host)->first();

        if (!$brand || !$brand->capability) {
            // Manejo de caso en que no se encuentra brand
            // Retorna null o algún valor por defecto
            return null;
        }

        return $brand->capability->code_name;
    }
}

if (!function_exists('brand_setting')) {
    function brand_setting($key, $default = null)
    {
        // Puedes inyectar desde el service container
        return app(BrandSettingsRepository::class)->get($key, null, $default);
    }
}

if (!function_exists('brand_asset')) {

    /**
     * Every Brand has its media repository; for example: admin.client.com and
     * admin.client2.com
     * 
     * This helper return the asset URL with the proper root according to the
     * owner of the asset
     * 
     * @param string $asset
     * @return string
     */
    function brand_asset($asset, $brand = null, $https = true)
    {
        if (!$brand)
            $brand = get_current_brand() ? get_current_brand() : request()->get('brand');

        if ($https)
            $https = !env('BYPASS_HTTPS', false);

        return ($https ? "https://" : "http://") . $brand->allowed_host . '/' . trim($asset, '/');
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
