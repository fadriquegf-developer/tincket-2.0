<?php

use Illuminate\Support\Facades\Route;
use Backpack\ActivityLog\Http\Controllers\ActivityLogCrudController;

// ==========================================
// Rutas para superadmins (independiente de capability)
// ==========================================

Route::middleware(['web', 'auth:backpack', 'is_superadmin'])->group(function () {
    // Configuraciones avanzadas
    Route::crud('custom-settings/brand', \App\Http\Controllers\Admin\SettingsBrandCrudController::class);
    Route::crud('custom-settings/advanced', \App\Http\Controllers\Admin\SettingsAdvancedCrudController::class);
    Route::crud('custom-settings/tpv', \App\Http\Controllers\Admin\SettingsTpvCrudController::class);

    Route::crud('activity-log', ActivityLogCrudController::class);
    Route::get('activity-log/causer', [ActivityLogCrudController::class, 'getCauserOptions']);
    Route::get('activity-log/subject', [ActivityLogCrudController::class, 'getSubjectOptions']);
});