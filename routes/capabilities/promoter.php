<?php

use Illuminate\Support\Facades\Route;

// ==========================================
// Rutas EXCLUSIVAS para PROMOTER
// ==========================================

Route::middleware(['web', 'auth:backpack', 'capability:promoter'])->group(function () {
    // Import Códigos Sesión - Solo Promoter
    Route::post('session/{id}/import-codes', [\App\Http\Controllers\Admin\SessionCrudController::class, 'importCodes'])->name('session.import-codes');
    Route::crud('inscription', \App\Http\Controllers\Admin\InscriptionCrudController::class);
    Route::put('/inscription/update-price', [\App\Http\Controllers\Admin\InscriptionCrudController::class, 'updatePrice'])->name('inscription.update.price');
    Route::get('inscription/{inscription}/generate', [\App\Http\Controllers\Admin\InscriptionCrudController::class, 'generate'])->name('inscription.generate');
    
    // Configuración Promotor
    Route::crud('custom-settings/promotor', \App\Http\Controllers\Admin\SettingsPromotorCrudController::class);
});
