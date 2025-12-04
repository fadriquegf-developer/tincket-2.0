<?php

/*
 * The routes contained in this file are public accessible like HTML version
 * of Inscription confirmation
 */

use App\Http\Controllers\Open\v1\EventsController;
use App\Http\Controllers\Open\v1\TaxonomyController;

/*
  |--------------------------------------------------------------------------
  | Public Endpoints
  |--------------------------------------------------------------------------
 */

Route::group(['prefix' => 'public', 'middleware' => ['setBrand']], function () {
  Route::get('inscription/{inscription}', [\App\Http\Controllers\Open\InscriptionController::class, 'getInscriptionTicket'])->name('open.inscription.pdf');
  Route::get('gift-card/{gift}', [\App\Http\Controllers\Open\GiftCardController::class, 'getPDF'])->name('open.gift_card.pdf');
  Route::get('gift-card/{gift}/download', [\App\Http\Controllers\Open\GiftCardController::class, 'download'])->name('open.gift_card.download');
  Route::get('cart/{cart}/download', [\App\Http\Controllers\Open\CartController::class, 'download'])->name('open.cart.download');
  Route::get('cart/{cart}/pack/{pack}/download', [\App\Http\Controllers\Open\CartController::class, 'downloadPack'])->name('open.cart.pack.download');

  /*
  |--------------------------------------------------------------------------
  | Version 1
  |--------------------------------------------------------------------------
  */
  Route::group(['prefix' => 'v1', 'namespace' => 'Open\v1', 'as' => 'open.api1.', 'middleware' => ['apiToken', 'apilocalization']], function () {
    Route::get('events', [EventsController::class, 'index'])->name('events.index');
    Route::get('taxonomies', [TaxonomyController::class, 'index'])->name('taxonomies.index');
  });
});
