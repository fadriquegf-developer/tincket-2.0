<?php

use Illuminate\Support\Facades\Route;

// ==========================================
// Rutas EXCLUSIVAS para ENGINE
// ==========================================

Route::middleware(['web', 'auth:backpack', 'capability:engine'])->group(function () {
    // Motor - Solo Engine
    Route::crud('brand', \App\Http\Controllers\Admin\BrandCrudController::class);
    Route::crud('capability', \App\Http\Controllers\Admin\CapabilityCrudController::class);
    Route::crud('application', \App\Http\Controllers\Admin\ApplicationCrudController::class);
    Route::crud('update-notification', \App\Http\Controllers\Admin\UpdateNotificationCrudController::class);
    Route::get('update-notification/{id}/read', [\App\Http\Controllers\Admin\UpdateNotificationCrudController::class, 'updateToReadedNotification']);
    Route::get('update-notification/all/set-read', [\App\Http\Controllers\Admin\UpdateNotificationCrudController::class, 'updateAllToReadedNotification']);
    Route::crud('job', \App\Http\Controllers\Admin\JobCrudController::class);
    Route::crud('failed-job', \App\Http\Controllers\Admin\FailedJobCrudController::class);
    Route::get('failed-job/{id}/retry', [\App\Http\Controllers\Admin\FailedJobCrudController::class, 'retry'])->name('failed-job.retry');

    // Administración avanzada - Solo Engine
    Route::crud('permission', \App\Http\Controllers\Admin\PermissionCrudController::class);
});
