<?php

use Illuminate\Support\Facades\Route;

// ==========================================
// Rutas de eventos compartidas
// ==========================================

Route::middleware(['web', 'auth:backpack'])->group(function () {
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
});
