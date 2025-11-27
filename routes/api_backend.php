<?php

/*
 * All this API endpoints are privates and are used from backend with and 
 * authenticated user
 */
Route::group(['prefix' => 'api', 'namespace' => 'ApiBackend', 'as' => 'apibackend.'], function () {
    Route::get('entity', [\App\Http\Controllers\ApiBackend\EntityApiBackendController::class, 'search'])->name('entity.search');

    Route::get('client/subscribed', [\App\Http\Controllers\ApiBackend\ClientApiBackendController::class, 'subscribed'])->name('client.subscribed');
    Route::get('client/subscribed-to', [\App\Http\Controllers\ApiBackend\ClientApiBackendController::class, 'subscribedTo'])->name('client.subscribed-to');
    Route::get('client/autocomplete', [\App\Http\Controllers\ApiBackend\ClientApiBackendController::class, 'autocomplete'])->name('client.autocomplete');

    Route::get('session', [\App\Http\Controllers\ApiBackend\SessionApiBackendController::class, 'search'])->name('session.search');
    Route::get('session/{session}/configuration', [\App\Http\Controllers\ApiBackend\SessionApiBackendController::class, 'getConfiguration'])->name('session.configuration');
    Route::get('/session/{session}/rates', [\App\Http\Controllers\ApiBackend\SessionApiBackendController::class, 'getRates']);

    Route::get('pack/{pack}', [\App\Http\Controllers\ApiBackend\PackApiBackendController::class, 'show'])->name('pack.show');
    Route::get('gift-card/validate', [\App\Http\Controllers\ApiBackend\GiftCardApiBackendController::class, 'validateCode'])->name('gift_card.validate');

    Route::group(['prefix' => 'statistics', 'namespace' => 'Statistics'], function () {
        Route::get('sales', [\App\Http\Controllers\ApiBackend\Statistics\StatisticsSalesController::class, 'get']);
        Route::get('balance', [\App\Http\Controllers\ApiBackend\Statistics\StatisticsBalanceController::class, 'get']);
    });

    Route::group(['prefix' => 'mailing', 'as' => 'mailing.'], function () {
    Route::get('test/{mailing}', [\App\Http\Controllers\ApiBackend\MailingApiBackendController::class, 'test'])->name('test');
});
});
