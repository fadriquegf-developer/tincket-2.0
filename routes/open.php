<?php

/*
 * The routes contained in this file are public accessible like HTML version
 * of Inscription confirmation
 */

/*
  |--------------------------------------------------------------------------
  | Public Endpoints
  |--------------------------------------------------------------------------
 */
Route::group([ 'prefix'=>'public', 'middleware' => ['setBrand']], function () {
  Route::get('inscription/{inscription}',[\App\Http\Controllers\Open\InscriptionController::class, 'getInscriptionTicket'])->name('open.inscription.pdf');
  /* Route::get('gift-card/{gift}', 'Open\GiftCardController@getPDF')->name('open.gitf_card.pdf');
  Route::get('gift-card/{gift}/download', 'Open\GiftCardController@download')->name('open.gitf_card.download');
  Route::get('order/{order}/download/{token}.pdf', 'Open\InscriptionsSetController@downloadOrderPdf')->name('open.order.download');*/
  Route::get('cart/{cart}/download', [\App\Http\Controllers\Open\CartController::class,'download'])->name('open.cart.download'); 

  /*
  |--------------------------------------------------------------------------
  | Version 1
  |--------------------------------------------------------------------------
  */
  /* Route::group(['prefix' => 'v1', 'namespace' => 'Open\v1', 'as' => 'open.api1.', 'middleware' => ['apiToken', 'cors', 'apilocalization']], function () {
    Route::get('events', 'EventsController@index')->name('events.index');
    Route::get('taxonomies', 'TaxonomyController@index')->name('taxonomies.index');
  }); */
});