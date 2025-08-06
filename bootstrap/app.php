<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'apiToken' => \App\Http\Middleware\CheckApiToken::class,
            'capability' => \App\Http\Middleware\CheckBrandCapability::class,
            /* Midleware de package de cambio de idioma */
            'localize' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class,
            'localizationRedirect' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect' => \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
            'localeCookieRedirect' => \Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect::class,
            'localeViewPath' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class,
            'setBrand' => \App\Http\Middleware\SetBrand::class
        ]);
        
        $middleware->web(append: [
            \Backpack\LanguageSwitcher\Http\Middleware\LanguageSwitcherMiddleware::class,
            \App\Http\Middleware\CheckUserBrand::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\CheckApiCredentials::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
