<?php


namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;


class AppServiceProvider extends ServiceProvider
{
    public function boot(Router $router)
    {
        Schema::defaultStringLength(191);

        Authenticate::redirectUsing(fn($request) => route('backpack.auth.login'));

        // Rate limiter para mailings por brand
        RateLimiter::for('mailings-brand-*', function ($job) {
            $brandId = str_replace('mailings-brand-', '', $job->middleware[1]->key);

            return Limit::perHour(100) // 100 mailings por hora por brand
                ->by('brand-' . $brandId);
        });

        $this->loadLanguages();
    }

    public function register()
    {
        $this->app->singleton(\App\Services\BrandSettingsRepository::class, function ($app) {
            return new \App\Services\BrandSettingsRepository();
        });
    }

    private function loadLanguages()
    {
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return;
        }

        try {
            if (!Schema::hasTable('settings')) {
                return;
            }

            // Obtener todos los idiomas configurados en settings
            $settings = Setting::where('key', 'like', 'laravellocalization.supportedLocales.%.name')
                ->get();

            $locales = [];

            foreach ($settings as $setting) {
                // Extraer el código del idioma (ca, es, en, etc.)
                if (preg_match('/laravellocalization\.supportedLocales\.([^.]+)\.name/', $setting->key, $matches)) {
                    $localeCode = $matches[1];
                    $locales[$localeCode] = $setting->value;
                }
            }

            if (!empty($locales)) {
                // Sobrescribir la configuración de backpack/crud
                config(['backpack.crud.locales' => $locales]);
            }
        } catch (\Exception $e) {
            \Log::debug('No se pudieron cargar los idiomas desde settings: ' . $e->getMessage());
        }
    }
}
