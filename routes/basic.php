<?php

use Illuminate\Support\Facades\Route;

// Rutas CRUD permitidas para 'basic'
Route::middleware(['web', 'auth:backpack', 'capability:basic'])->group(function () {

    Route::crud('role', \App\Http\Controllers\Admin\RoleCrudController::class);

    Route::crud('post', \App\Http\Controllers\Admin\PostCrudController::class);

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
    Route::post('session/{id}/import-codes', [\App\Http\Controllers\Admin\SessionCrudController::class, 'importCodes'])->name('session.import-codes');

    Route::crud('cart', \App\Http\Controllers\Admin\CartCrudController::class);
    Route::post('cart/{id}/restore', [\App\Http\Controllers\Admin\CartCrudController::class, 'restore'])->name('cart.restore');
    Route::post('cart/recovery', [\App\Http\Controllers\Admin\CartCrudController::class, 'recovery'])->name('crud.cart.recovery');
    Route::get('cart/{cart}/download', [\App\Http\Controllers\Admin\CartCrudController::class, 'download'])->name('crud.cart.download');
    Route::post('cart/{id}/change-client', [\App\Http\Controllers\Admin\CartCrudController::class, 'changeClient'])->name('crud.cart.change-client');
    Route::put('cart/{id}', [\App\Http\Controllers\Admin\CartCrudController::class, 'updateComment'])->name('cart.update');
    Route::get('cart/{cart}/regenerate', [\App\Http\Controllers\Admin\CartCrudController::class, 'regeneratePDF']);
    Route::get('cart/{cart}/send-mail-payment', [\App\Http\Controllers\Admin\CartCrudController::class, 'sendPaymentEmail']);
    Route::post('cart/payment-office', [\App\Http\Controllers\Admin\CartCrudController::class, 'paymentOffice'])->name('crud.cart.payment-office');
    Route::post('cart/{cart}/change-gateway', [\App\Http\Controllers\Admin\CartCrudController::class, 'changeGateway'])->name('crud.cart.change-gateway');

    Route::crud('pack', \App\Http\Controllers\Admin\PackCrudController::class);
    Route::get('pack/{id}/sales', [\App\Http\Controllers\Admin\PackCrudController::class, 'sales']);

    Route::crud('gift-card', \App\Http\Controllers\Admin\GiftCardCrudController::class);
    Route::crud('page', \App\Http\Controllers\Admin\PageCrudController::class);
    Route::crud('menu-item', \App\Http\Controllers\Admin\MenuItemCrudController::class);
    Route::crud('taxonomy', \App\Http\Controllers\Admin\TaxonomyCrudController::class);

    Route::crud('censu', \App\Http\Controllers\Admin\CensuCrudController::class);
    Route::post('censu/import-codes', [\App\Http\Controllers\Admin\CensuCrudController::class, 'importCodes'])->name('censu.import-codes');

    Route::crud('mailing', \App\Http\Controllers\Admin\MailingCrudController::class);
    Route::get('mailing/{mailing}/send', [\App\Http\Controllers\Admin\MailingCrudController::class, 'send']);

    Route::get('statistics/sales', [\App\Http\Controllers\Admin\StatisticsController::class, 'indexSales'])->name('statistics.sales');
    Route::get('statistics/balance', [\App\Http\Controllers\Admin\StatisticsController::class, 'indexBalance'])->name('statistics.balance');
    Route::crud('statistics/client-sales', \App\Http\Controllers\Admin\ClientSalesCrudController::class);

    Route::crud('code', \App\Http\Controllers\Admin\CodeCrudController::class);
    Route::get('code/generate-code', [\App\Http\Controllers\Admin\CodeCrudController::class,'generateCode']);
    Route::get('code/info-promotor/{promotor_id}', [\App\Http\Controllers\Admin\CodeCrudController::class,'infoPromotor']);
    Route::post('code/info-promotor', [\App\Http\Controllers\Admin\CodeCrudController::class,'storeInfoPromotor']);

    Route::crud('register-input', \App\Http\Controllers\Admin\RegisterInputCrudController::class);

    Route::resource('ticket-office', \App\Http\Controllers\Admin\TicketOfficeController::class);
});
