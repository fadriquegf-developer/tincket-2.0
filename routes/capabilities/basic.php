<?php

use Illuminate\Support\Facades\Route;

// ==========================================  
// Rutas EXCLUSIVAS para BASIC
// ==========================================

Route::middleware(['web', 'auth:backpack', 'capability:basic'])->group(function () {
    // Roles - Solo Basic
    Route::crud('role', \App\Http\Controllers\Admin\RoleCrudController::class);

    // CMS - Solo Basic
    Route::crud('post', \App\Http\Controllers\Admin\PostCrudController::class);
    Route::crud('page', \App\Http\Controllers\Admin\PageCrudController::class);
    Route::crud('menu-item', \App\Http\Controllers\Admin\MenuItemCrudController::class);
    Route::crud('taxonomy', \App\Http\Controllers\Admin\TaxonomyCrudController::class);
    Route::crud('mailing', \App\Http\Controllers\Admin\MailingCrudController::class);
    Route::get('mailing/{mailing}/send', [\App\Http\Controllers\Admin\MailingCrudController::class, 'send']);

    // CRM Avanzado - Solo Basic
    Route::crud('pack', \App\Http\Controllers\Admin\PackCrudController::class);
    Route::get('pack/{id}/sales', [\App\Http\Controllers\Admin\PackCrudController::class, 'sales']);
    Route::crud('gift-card', \App\Http\Controllers\Admin\GiftCardCrudController::class);
    Route::crud('censu', \App\Http\Controllers\Admin\CensuCrudController::class);
    Route::post('censu/import-codes', [\App\Http\Controllers\Admin\CensuCrudController::class, 'importCodes'])->name('censu.import-codes');
    Route::crud('inscription', \App\Http\Controllers\Admin\InscriptionCrudController::class);
    Route::get('inscription/{inscription}/generate', [\App\Http\Controllers\Admin\InscriptionCrudController::class, 'generate'])->name('inscription.generate');
    Route::put('/inscription/update-price', [\App\Http\Controllers\Admin\InscriptionCrudController::class, 'updatePrice'])->name('inscription.update.price');

    // Import/Export Clientes - Solo Basic
    Route::post('client/import', [\App\Http\Controllers\Admin\ClientCrudController::class, 'import'])->name('client.import');
    Route::get('clients/to-mailing', [\App\Http\Controllers\Admin\ClientCrudController::class, 'toMailing'])->name('client.to-mailing');

    // Import Códigos Sesión - Solo Basic
    Route::post('session/{id}/import-codes', [\App\Http\Controllers\Admin\SessionCrudController::class, 'importCodes'])->name('session.import-codes');

    // Estadísticas - Solo Basic
    Route::get('statistics/sales', [\App\Http\Controllers\Admin\StatisticsController::class, 'indexSales'])->name('statistics.sales');
    Route::get('statistics/balance', [\App\Http\Controllers\Admin\StatisticsController::class, 'indexBalance'])->name('statistics.balance');
    Route::crud('statistics/client-sales', \App\Http\Controllers\Admin\ClientSalesCrudController::class);

    // Gráficas Dashboard - Solo Basic
    Route::get('charts/sales-evolution', [\App\Http\Controllers\Admin\Charts\SalesEvolutionChartController::class, 'response'])
        ->name('charts.sales-evolution.index');
    Route::get('charts/sales-by-channel', [\App\Http\Controllers\Admin\Charts\SalesByChannelChartController::class, 'response'])
        ->name('charts.sales-by-channel.index');
    Route::get('charts/top-events', [\App\Http\Controllers\Admin\Charts\TopEventsChartController::class, 'response'])
        ->name('charts.top-events.index');
    Route::get('charts/space-occupancy', [\App\Http\Controllers\Admin\Charts\SpaceOccupancyChartController::class, 'response'])
        ->name('charts.space-occupancy.index');

    // Códigos y Register Inputs - Solo Basic
    Route::crud('code', \App\Http\Controllers\Admin\CodeCrudController::class);
    Route::get('code/generate-code', [\App\Http\Controllers\Admin\CodeCrudController::class, 'generateCode']);
    Route::get('code/info-promotor/{promotor_id}', [\App\Http\Controllers\Admin\CodeCrudController::class, 'infoPromotor']);
    Route::post('code/info-promotor', [\App\Http\Controllers\Admin\CodeCrudController::class, 'storeInfoPromotor']);
    Route::crud('register-input', \App\Http\Controllers\Admin\RegisterInputCrudController::class);

    // Sync Logs - Solo Basic
    Route::crud('log-sync', \App\Http\Controllers\Admin\SyncValidationCrudController::class);
});
