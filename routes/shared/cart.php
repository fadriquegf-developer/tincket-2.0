<?php

use Illuminate\Support\Facades\Route;

// ==========================================
// Rutas de carrito compartidas  
// ==========================================

Route::middleware(['web', 'auth:backpack'])->group(function () {
    Route::crud('cart', \App\Http\Controllers\Admin\CartCrudController::class);
    Route::get('cart/{id}/restore', [\App\Http\Controllers\Admin\CartCrudController::class, 'restore'])->name('cart.restore');
    Route::get('cart/{id}/show-trashed', [\App\Http\Controllers\Admin\CartCrudController::class, 'showTrashed'])->name('cart.show-trashed');
    Route::post('cart/recovery', [\App\Http\Controllers\Admin\CartCrudController::class, 'recovery'])->name('crud.cart.recovery');
    Route::get('cart/{cart}/download', [\App\Http\Controllers\Admin\CartCrudController::class, 'download'])->name('crud.cart.download');
    Route::post('cart/{id}/change-client', [\App\Http\Controllers\Admin\CartCrudController::class, 'changeClient'])->name('crud.cart.change-client');
    Route::put('cart/{id}', [\App\Http\Controllers\Admin\CartCrudController::class, 'updateComment'])->name('cart.update');
    Route::get('cart/{cart}/regenerate', [\App\Http\Controllers\Admin\CartCrudController::class, 'regeneratePDF']);
    Route::get('cart/{cart}/send-mail-payment', [\App\Http\Controllers\Admin\CartCrudController::class, 'sendPaymentEmail']);
    Route::post('cart/payment-office', [\App\Http\Controllers\Admin\CartCrudController::class, 'paymentOffice'])->name('crud.cart.payment-office');
    Route::post('cart/{cart}/change-gateway', [\App\Http\Controllers\Admin\CartCrudController::class, 'changeGateway'])->name('crud.cart.change-gateway');
    Route::post('cart/{id}/mark-refunded', [\App\Http\Controllers\Admin\CartCrudController::class, 'markRefunded'])->name('crud.cart.mark-refunded');

    // Ticket Office
    Route::resource('ticket-office', \App\Http\Controllers\Admin\TicketOfficeController::class)
        ->only(['index', 'create', 'store']);
});
