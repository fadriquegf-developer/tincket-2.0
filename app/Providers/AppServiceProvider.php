<?php


namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Auth\Middleware\Authenticate;


class AppServiceProvider extends ServiceProvider
{
    public function boot(Router $router)
    {
        Authenticate::redirectUsing(fn ($request) => route('backpack.auth.login'));
    }

    public function register()
    {
        $this->app->singleton(\App\Services\BrandSettingsRepository::class, function ($app) {
            return new \App\Services\BrandSettingsRepository();
        });
    }
}

