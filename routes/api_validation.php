<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "apivalidation" middleware group. Enjoy building your API!
| To allow one aplication for all validators this api not use Middleware\CheckApiCredentials
|
*/

use App\Http\Controllers\ApiValidation\AuthController;
use App\Http\Controllers\ApiValidation\ValidationController;

Route::group(['prefix' => 'apivalidation', 'as' => 'apivalidation.'], function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::group(['middleware' => ['auth:apivalidation']], function () {
        Route::controller(ValidationController::class)->group(function () {
            Route::get('events', 'getEvents');
            Route::get('events/{id}/sessions', 'getSessions');
            Route::get('events/{id}/download', 'downloadEvent');
            Route::post('events/{id}/sync', 'syncEvent');
            Route::post('events/{id}/sync-logs', 'syncEventLogs');
            Route::post('check/{id}', 'check'); //->middleware('permission:manage_validations');
            Route::post('out/{id}', 'outEvent'); //->middleware('permission:manage_validations');
        });

        Route::post('logout', [AuthController::class, 'logout']);
    });
});
