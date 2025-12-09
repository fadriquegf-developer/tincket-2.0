<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Brand;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;

class CheckBrandHost
{
    /**
     * Almacena la marca que se detecta.
     *
     * @var Brand|null
     */
    private ?Brand $brand = null;

    /**
     * Maneja la solicitud entrante.
     *
     * @param  Request  $request
     * @param  Closure(Request): Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        $this->brand = $this->setBrandInformation($request);

        if ($this->brand !== null) {
            $this->loadBrandConfig($this->brand->code_name);

            if ($this->brand->capability === 'engine') {
                $this->loadEngineLocales();
            }

            return $next($request);
        }

        abort(403, 'Access denied');
    }

    /**
     * Fusiona las configuraciones específicas de la marca sobre la configuración por defecto.
     *
     * @param  string  $codeName
     * @return void
     */
    public function loadBrandConfig(string $codeName): void
    {
        $this->bootstrapConfig();
        $this->loadBrandConfigFromFilesystem($codeName);
        $this->loadBrandConfigFromDatabase($codeName);
        $this->loadBrandDisks($codeName);
        $this->configureElFinderBrandRoot($codeName);
    }

    /**
     * Carga las locales específicas para "engine" desde la configuración de la marca.
     *
     * @return void
     */
    private function loadEngineLocales(): void
    {
        $localesOffset = 'backpack.crud.locales';
        foreach (config('brand') as $key => $value) {
            if (config()->has("brand.{$key}.{$localesOffset}")) {
                $languages = config("brand.{$key}.{$localesOffset}");
                foreach ($languages as $langKey => $language) {
                    config(["{$localesOffset}.{$langKey}" => $language]);
                }
            }
        }
    }

    /**
     * Establece la información de la marca en la request usando un helper (get_current_brand)
     * y almacena datos adicionales (ID, code_name y capacidades) en los atributos.
     *
     * @param  Request  $request
     * @return Brand|null
     */
    public function setBrandInformation(Request $request): ?Brand
    {
        $brand = get_current_brand();

        if (!$brand) {
            $brand = request()->get('brand');
        }

        if ($brand !== null) {
            // Asignamos la marca completa y algunos atributos clave
            $request->attributes->set('brand', $brand);
            $request->attributes->set('brand.id', $brand->id);
            $request->attributes->set('brand.code_name', $brand->code_name);

            // Dado que ahora la marca tiene una única capability, inyectamos su código
            $request->attributes->set('brand.capability', $brand->capability?->code_name);

            // En este contexto, el "user" se toma del usuario autenticado
            $request->attributes->set('user', backpack_auth()->user());
        }

        return $brand;
    }


    /**
     * Configura el root de elFinder para que utilice el disco aislado de la marca.
     *
     * @param  string  $codeName
     * @return void
     */
    private function configureElFinderBrandRoot(string $codeName): void
    {
        $brandPath = "uploads/{$codeName}";
        $fullPath = storage_path("app/public/{$brandPath}");

        // Crear directorio si no existe
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        // ✅ Configurar ROOTS en lugar de DISKS
        config([
            'elfinder.roots' => [
                [
                    'driver' => 'LocalFileSystem',
                    'path' => $fullPath,
                    'URL' => asset("storage/{$brandPath}"),
                    'alias' => $codeName,
                    'accessControl' => new \App\Services\ElfinderAccessControl(),
                    // Slugify nombres de archivos
                    'uploadOrder' => ['deny', 'allow'],

                    // Validación de nombres de archivo
                    'acceptedName' => '/^[a-z0-9\-_\.]+$/i', // Solo permite letras, números, guiones y puntos

                    // Límite de tamaño (en bytes) - ejemplo: 10MB
                    'uploadMaxSize' => '10M', // o 10485760 para 10MB en bytes

                    // Plugin para modificar nombres
                    'plugin' => [
                        'Sanitizer' => [
                            'enable' => true,
                            'targets' => ['\\ElFinder\\Sanitizer'], // Clase personalizada
                        ],
                    ],
                    'attributes' => [
                        [
                            'pattern' => '/^_.*/',  // Ocultar carpetas que empiecen con _
                            'read' => false,
                            'write' => false,
                            'locked' => true,
                            'hidden' => true
                        ]
                    ]
                ]
            ],
        ]);
    }

    /**
     * Crea discos para la marca actual en la configuración de Filesystems.
     *
     * @param  string  $codeName
     * @return void
     */
    private function loadBrandDisks(string $codeName): void
    {
        config([
            'filesystems.disks' => array_merge(
                config('filesystems.disks'),
                [
                    'brand' => [
                        'driver' => 'local',
                        'root' => storage_path("app/public/uploads/{$codeName}"),
                        'visibility' => 'public',
                    ],
                    'mailings' => [
                        'driver' => 'local',
                        'root' => storage_path("app/mailing/{$codeName}"),
                    ],
                ]
            ),
        ]);
    }

    /**
     * Reestablece la configuración original para evitar que configuraciones de marcas previas se mantengan.
     *
     * @return void
     */
    private function bootstrapConfig(): void
    {
        $originalConfig = config()->all();

        // Eliminamos las claves que no deben mezclarse
        $configsToRemove = ['base', 'mail', 'brand', 'clients', 'settings', 'ywt'];
        foreach ($configsToRemove as $c) {
            if (isset($originalConfig[$c])) {
                unset($originalConfig[$c]);
            }
        }

        if (file_exists(app()->getCachedConfigPath())) {
            unlink(app()->getCachedConfigPath());
        }

        (new LoadConfiguration())->bootstrap(app());

        config(array_merge(config()->all(), $originalConfig));
    }

    /**
     * Carga la configuración de la marca desde los archivos del sistema.
     *
     * @param  string  $codeName
     * @return void
     */
    private function loadBrandConfigFromFilesystem(string $codeName): void
    {
        // Para estas claves se quiere que la configuración NO se haga merge con la base
        $stopRecursiveMergeAtOffset = ['backpack.crud.locales'];

        $brandConfigs = Arr::dot(config("brand.{$codeName}", []));

        if (!empty($brandConfigs)) {
            foreach ($stopRecursiveMergeAtOffset as $offsetKey) {
                if (config()->has("brand.{$codeName}.{$offsetKey}")) {
                    // Se elimina la configuración en ese offset
                    config()->offsetUnset($offsetKey);
                }
            }
            $defaultConfigs = Arr::dot(config()->all());
            config(array_merge($defaultConfigs, $brandConfigs));

            // Actualizamos el locale del traductor según la configuración actual
            app('translator')->setLocale(config('app.locale'));
        }
    }

    /**
     * Carga la configuración de la marca desde la base de datos.
     *
     * @param  string  $codeName
     * @return void
     */
    private function loadBrandConfigFromDatabase(string $codeName): void
    {
        $brand = $this->brand ?? Brand::where('code_name', $codeName)->first();
        $defaultConfigs = config()->all();
        $brandConfigs = [];

        $hasCustomBackpackLocales = false;
        $hasCustomClientLocales = false;

        if ($brand) {
            $brand->settings->each(function ($setting) use (&$brandConfigs, &$hasCustomBackpackLocales, &$hasCustomClientLocales) {
                Arr::set($brandConfigs, $setting->key, $setting->value);

                if (str_starts_with($setting->key, 'backpack.crud.locales')) {
                    $hasCustomBackpackLocales = true;
                } elseif (str_starts_with($setting->key, 'laravellocalization.supportedLocales')) {
                    $hasCustomClientLocales = true;
                }
            });
        }

        if ($hasCustomBackpackLocales && isset($defaultConfigs['backpack']['crud']['locales'])) {
            $defaultConfigs['backpack']['crud']['locales'] = [];
        }
        if ($hasCustomClientLocales && isset($defaultConfigs['laravellocalization']['supportedLocales'])) {
            $defaultConfigs['laravellocalization']['supportedLocales'] = [];
        }

        config(array_merge(Arr::dot($defaultConfigs), Arr::dot($brandConfigs)));

        $laravelLocalizationLocales = config('laravellocalization.supportedLocales', []);
        $backpackLocales = [];

        foreach ($laravelLocalizationLocales as $code => $localeData) {
            $nativeName = $localeData['native'] ?? $localeData['name'] ?? $code;
            $backpackLocales[$code] = Str::ucfirst($nativeName);
        }

        if (!empty($backpackLocales)) {
            config(['backpack.crud.locales' => $backpackLocales]);
        }

        if (!empty($backpackLocales)) {
            config(['backpack.crud.locales' => $backpackLocales]);
        }

        // Respetar el locale de sesión si existe
        if (session()->has('locale')) {
            $sessionLocale = session('locale');
            $availableLocales = config('backpack.crud.locales', []);

            if (array_key_exists($sessionLocale, $availableLocales)) {
                app()->setLocale($sessionLocale);
            } else {
                $firstLocale = array_key_first($availableLocales);
                if ($firstLocale) {
                    app()->setLocale($firstLocale);
                    session(['locale' => $firstLocale]);
                }
            }
        } else {
            app()->setLocale(app()->getLocale());
        }
    }
}
