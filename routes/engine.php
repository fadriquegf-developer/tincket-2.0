<?php

use Illuminate\Support\Facades\Route;

// Rutas CRUD permitidas para 'engine'
Route::middleware(['web', 'auth:backpack', 'capability:engine', ])->group(function () {
    //Route::crud('user', \App\Http\Controllers\Admin\UserCrudController::class);
    Route::crud('brand', \App\Http\Controllers\Admin\BrandCrudController::class);
    Route::crud('capability', \App\Http\Controllers\Admin\CapabilityCrudController::class);
    Route::crud('custom-settings/brand', \App\Http\Controllers\Admin\SettingsBrandCrudController::class);
    Route::crud('permission', \App\Http\Controllers\Admin\PermissionCrudController::class);
    Route::crud('application', \App\Http\Controllers\Admin\ApplicationCrudController::class);
    Route::crud('update-notification', \App\Http\Controllers\Admin\UpdateNotificationCrudController::class);
    Route::crud('job', \App\Http\Controllers\Admin\JobCrudController::class);
    Route::crud('failed-job', \App\Http\Controllers\Admin\FailedJobCrudController::class);
    Route::get('failed-job/{id}/retry', [\App\Http\Controllers\Admin\FailedJobCrudController::class, 'retry'])->name('failed-job.retry');

    Route::crud('event', \App\Http\Controllers\Admin\EventCrudController::class);
    Route::get('event/{id}/clone', [\App\Http\Controllers\Admin\EventCrudController::class, 'clone'])->name('event.clone');

    Route::crud('session', \App\Http\Controllers\Admin\SessionCrudController::class);
    Route::get('session/{id}/inscriptions', [\App\Http\Controllers\Admin\SessionCrudController::class, 'inscriptions'])->name('session.inscriptions');
    Route::get('{session}/inscriptions/export-excel', [\App\Http\Controllers\Admin\SessionCrudController::class, 'exportExcel'])->name('session.inscriptions.exportExcel');
    Route::get('{session}/inscriptions/print', [\App\Http\Controllers\Admin\SessionCrudController::class, 'printInscr'])->name('session.inscriptions.print');
    Route::get('session/{id}/liquidation', [\App\Http\Controllers\Admin\SessionCrudController::class, 'liquidation'])->name('session.liquidation');
    Route::get('session/{id}/regenerate', [\App\Http\Controllers\Admin\SessionCrudController::class, 'regenerate'])->name('session.regenerate');
    Route::get('session/{id}/pdf-errors', [\App\Http\Controllers\Admin\SessionCrudController::class, 'listPdfErrors'])->name('session.pdf_errors');
    Route::post('session/clone', [\App\Http\Controllers\Admin\SessionCrudController::class, 'cloneSessions'])->name('session.clone');
    Route::get('session/multi-create', [\App\Http\Controllers\Admin\SessionCrudController::class, 'multiCreate'])->name('session.multi-create');
    Route::post('session/multi-store', [\App\Http\Controllers\Admin\SessionCrudController::class, 'multiStore'])->name('session.multi-store');

    Route::get('update-notification/{id}/read', [\App\Http\Controllers\Admin\UpdateNotificationCrudController::class, 'updateToReadedNotification']);
    Route::get('update-notification/all/set-read', [\App\Http\Controllers\Admin\UpdateNotificationCrudController::class, 'updateAllToReadedNotification']);
});
