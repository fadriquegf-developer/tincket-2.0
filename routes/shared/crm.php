<?php

// ==========================================
// Rutas CRM compartidas por basic y promoter
// ==========================================

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth:backpack'])->group(function () {
    // Espacios y Localizaciones
    Route::crud('location', \App\Http\Controllers\Admin\LocationCrudController::class);
    Route::crud('space', \App\Http\Controllers\Admin\SpaceCrudController::class);
    Route::get('space-capacity/{space}', [\App\Http\Controllers\Admin\SpaceCrudController::class, 'getCapacity'])->name('space.capacity');
    Route::crud('zone', \App\Http\Controllers\Admin\ZoneCrudController::class);
    Route::crud('rate', \App\Http\Controllers\Admin\RateCrudController::class);

    // Formularios
    Route::crud('form', \App\Http\Controllers\Admin\FormCrudController::class);
    Route::crud('form-field', \App\Http\Controllers\Admin\FormFieldCrudController::class);
});
