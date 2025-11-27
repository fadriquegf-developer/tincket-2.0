<?php
// ==========================================
// Rutas compartidas por TODAS las capabilities
// ==========================================

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth:backpack'])->group(function () {
    // Dashboard - todos tienen acceso
    Route::get('dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('backpack.dashboard');
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('backpack');

    // Validaciones - todos pueden validar
    Route::crud('validation', \App\Http\Controllers\Admin\ValidationController::class);
    Route::post('validation/check', [\App\Http\Controllers\Admin\ValidationController::class, 'check'])->name('validation.check');

    Route::get(config('backpack.base.route_prefix') . '/set-locale/{locale}', function ($locale) {
        session(['locale' => $locale]);
        session()->save(); // Forzar guardar inmediatamente
        app()->setLocale($locale);

        return redirect()->back();
    })->name('language-switcher.locale')->middleware('web');
});
