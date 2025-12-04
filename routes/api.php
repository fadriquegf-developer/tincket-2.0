<?php

use Illuminate\Support\Facades\Route;



Route::group(['middleware' => 'api', 'prefix' => 'v1', 'as' => 'api1.'], function () {

    Route::group(['prefix' => 'taxonomy', 'as' => 'taxonomy.'], function () {
        Route::get('/', [\App\Http\Controllers\Api\v1\TaxonomyApiController::class, 'index'])->name('index');
        Route::get('{taxonomy}/{relation?}', [\App\Http\Controllers\Api\v1\TaxonomyApiController::class, 'show'])->name('show');
    });

    Route::group(['prefix' => 'event', 'as' => 'event.'], function () {
        Route::get('/', [\App\Http\Controllers\Api\v1\EventApiController::class, 'index'])->name('index');
        Route::get('all-next-events', [\App\Http\Controllers\Api\v1\EventApiController::class, 'allNextEvents'])->name('all-next-events');
        Route::get('{event}', [\App\Http\Controllers\Api\v1\EventApiController::class, 'show'])->name('show')->where('session', '[0-9]+');
        Route::get('{event}/sessions', [\App\Http\Controllers\Api\v1\EventApiController::class, 'getSessions'])->name('sessions');
    });

    Route::group(['prefix' => 'pack', 'as' => 'pack.'], function () {
        Route::get('/', [\App\Http\Controllers\Api\v1\PackApiController::class, 'index'])->name('index');
        Route::get('{pack}', [\App\Http\Controllers\Api\v1\PackApiController::class, 'show'])->name('show');
    });

    Route::group(['prefix' => 'session', 'as' => 'session.'], function () {
        Route::get('{session}', [\App\Http\Controllers\Api\v1\SessionApiController::class, 'show'])->name('show');
        Route::get('{session}/configuration', [\App\Http\Controllers\Api\v1\SessionApiController::class, 'configuration'])->name('configuration');
        Route::get('{session}/check-user-limit', [\App\Http\Controllers\Api\v1\SessionApiController::class, 'checkUserLimit'])->name('check-user-limit');
    });

    Route::group(['prefix' => 'cart', 'as' => 'cart.'], function () {
        Route::post('/', [\App\Http\Controllers\Api\v1\CartApiController::class, 'store'])->name('store');
        Route::get('{cart}', [\App\Http\Controllers\Api\v1\CartApiController::class, 'show'])->name('show');
        Route::get('{cart}/payment', [\App\Http\Controllers\Api\v1\CartApiController::class, 'getPayment'])->name('payment');
        Route::get('{cart}/check-duplicated-slots', [\App\Http\Controllers\Api\v1\CartApiController::class, 'checkDuplicated'])->name('check-duplicated');
        Route::get('{token}/payment-email', [\App\Http\Controllers\Api\v1\CartApiController::class, 'getPaymentForEmail'])->name('payment-email');
        Route::get('{token}/check-payment', [\App\Http\Controllers\Api\v1\CartApiController::class, 'checkPaymentPaid'])->name('check-payment');
        Route::put('{cart}/{section}', [\App\Http\Controllers\Api\v1\CartApiController::class, 'update'])->name('update');
        Route::patch('{cart}/{section}', [\App\Http\Controllers\Api\v1\CartApiController::class, 'patch'])->name('patch');
        Route::delete('{cart}/{type}/{id}', [\App\Http\Controllers\Api\v1\CartApiController::class, 'destroy'])->name('destroy');
        Route::get('{cart}/extend-time', [\App\Http\Controllers\Api\v1\CartApiController::class, 'extendTime'])->name('extend-time');
        Route::get('{cart}/expired-time', [\App\Http\Controllers\Api\v1\CartApiController::class, 'expiredTime'])->name('expired-time');
        Route::get('{cart}/check-slots', [\App\Http\Controllers\Api\v1\CartApiController::class, 'checkSlotsAvailability'])->name('check-slots');
    });

    Route::group(['prefix' => 'payment', 'as' => 'payment.'], function () {
        Route::match(['GET', 'POST'], '/callback', [\App\Http\Controllers\Api\v1\PaymentApiController::class, 'callback'])->name('callback');
    });

    Route::group(['prefix' => 'client', 'as' => 'client.'], function () {
        Route::post('/', [\App\Http\Controllers\Api\v1\ClientApiController::class, 'store']);
        Route::post('subscribe/{token}', [\App\Http\Controllers\Api\v1\ClientApiController::class, 'subscribe']);
        Route::get('inputs/{brand}', [\App\Http\Controllers\Api\v1\ClientApiController::class, 'registerInputs']);
        Route::get('{client}', [\App\Http\Controllers\Api\v1\ClientApiController::class, 'show']);
        Route::put('{client}', [\App\Http\Controllers\Api\v1\ClientApiController::class, 'update']);
        Route::get('{client}/carts', [\App\Http\Controllers\Api\v1\ClientApiController::class, 'showCarts']);
        Route::post('verify-password', [\App\Http\Controllers\Api\v1\ClientApiController::class, 'verifyPassword']);
        Route::post('search', [\App\Http\Controllers\Api\v1\ClientApiController::class, 'search'])->name('search');
        Route::delete('{client}/password', [\App\Http\Controllers\Api\v1\ClientApiController::class, 'resetPassword'])->name('reset-password');
        Route::put('{client}/password', [\App\Http\Controllers\Api\v1\ClientApiController::class, 'setPassword'])->name('set-password');
        Route::delete('cart/inscription', [\App\Http\Controllers\Api\v1\ClientApiController::class, 'deleteInscription'])->name('delete-inscription');
        Route::get('check-soft-deleted/{email}', [\App\Http\Controllers\Api\v1\ClientApiController::class, 'checkSoftDeleted']);
        Route::get('check-email-exist/{email}', [\App\Http\Controllers\Api\v1\ClientApiController::class, 'checkEmailExist']);
    });

    Route::group(['prefix' => 'meta', 'as' => 'meta.'], function () {
        Route::get('model/{model}', [\App\Http\Controllers\Api\v1\MetaController::class, 'getModel']);
        Route::get('request/{request}', [\App\Http\Controllers\Api\v1\MetaController::class, 'getFormRequest']);
        Route::get('config/{path}', [\App\Http\Controllers\Api\v1\MetaController::class, 'getConfig'])->where('path', '.+');
        Route::get('settings', [\App\Http\Controllers\Api\v1\MetaController::class, 'getSettings']);
        Route::get('menu', [\App\Http\Controllers\Api\v1\MetaController::class, 'getMenu']);
        Route::get('initial-config', [\App\Http\Controllers\Api\v1\MetaController::class, 'getInitialConfig']);
    });

    Route::group(['prefix' => 'page', 'as' => 'page.'], function () {
        Route::get('{id}', [\App\Http\Controllers\Api\v1\PageApiController::class, 'show']);
    });

    Route::group(['prefix' => 'post', 'as' => 'post.'], function () {
        Route::get('/', [\App\Http\Controllers\Api\v1\PostApiController::class, 'index'])->name('index');
        Route::get('/{id}', [\App\Http\Controllers\Api\v1\PostApiController::class, 'show'])->name('show');
    });

    Route::group(['prefix' => 'form', 'as' => 'form.'], function () {
        Route::get('{form}', [\App\Http\Controllers\Api\v1\FormApiController::class, 'show'])->name('show');
        Route::post('{form}/{client}', [\App\Http\Controllers\Api\v1\FormApiController::class, 'store'])->name('store');
        Route::get('register-check/{client}', [\App\Http\Controllers\Api\v1\FormApiController::class, 'registerCheckRequired'])->name('registerCheckRequired');
    });

    Route::group(['prefix' => 'rate', 'as' => 'rate.'], function () {
        Route::get('{rate}/checkIsFull', [\App\Http\Controllers\Api\v1\RateApiController::class, 'rateIsFull'])->name('checkIsFull');
        Route::get('{rate}/checkCode', [\App\Http\Controllers\Api\v1\RateApiController::class, 'checkCode'])->name('checkCode');
        Route::get('{rate}/checkDni', [\App\Http\Controllers\Api\v1\RateApiController::class, 'checkDni'])->name('checkDni');
        Route::get('{rate}/checkEmail', [\App\Http\Controllers\Api\v1\RateApiController::class, 'checkEmail'])->name('checkEmail');
    });

    Route::group(['prefix' => 'contact', 'as' => 'contact.'], function () {
        Route::post('send', [\App\Http\Controllers\Api\v1\ContactApiController::class, 'send'])->name('send');
    });

    Route::group(['prefix' => 'location', 'as' => 'location.'], function () {
        Route::get('regions', [\App\Http\Controllers\Api\v1\LocationApiController::class, 'getRegionsAndCities'])->name('regions');
    });

    Route::group(['prefix' => 'partner', 'as' => 'partner.'], function () {
        Route::get('/', [\App\Http\Controllers\Api\v1\PartnerApiController::class, 'index'])->name('index');
        Route::get('{code_name}', [\App\Http\Controllers\Api\v1\PartnerApiController::class, 'show'])->name('show');
        Route::get('{id}/events', [\App\Http\Controllers\Api\v1\PartnerApiController::class, 'events'])->name('events');
        Route::post('/', [\App\Http\Controllers\Api\v1\PartnerApiController::class, 'store'])->name('store');
    });
});
