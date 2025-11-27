<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'apiToken' => \App\Http\Middleware\CheckApiToken::class,
            'capability' => \App\Http\Middleware\CheckCapability::class,
            'is_superadmin' => \App\Http\Middleware\IsSuperAdmin::class,
            'setBrand' => \App\Http\Middleware\SetBrand::class,
            /* Midleware de package de cambio de idioma */
            'localize' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class,
            'localizationRedirect' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect' => \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
            'localeCookieRedirect' => \Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect::class,
            'localeViewPath' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class,
            'throttle.login' => \App\Http\Middleware\LoginThrottleMiddleware::class,
            'apilocalization' => \App\Http\Middleware\ApiLocalization::class,

            // spatie/laravel-permission
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
        //$middleware->prepend(HandleCors::class);

        $middleware->web(append: [
            \Backpack\LanguageSwitcher\Http\Middleware\LanguageSwitcherMiddleware::class,
            \App\Http\Middleware\CheckBrandHost::class,
            \App\Http\Middleware\CheckUserBrand::class
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\CheckApiCredentials::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
