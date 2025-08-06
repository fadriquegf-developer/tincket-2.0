<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth:backpack'])->group(function () {
    Route::crud('client', \App\Http\Controllers\Admin\ClientCrudController::class);
    Route::post('client/bulk-restore', [\App\Http\Controllers\Admin\ClientCrudController::class, 'bulkRestore'])->name('client.bulkRestore');
    Route::post('client/import', [\App\Http\Controllers\Admin\ClientCrudController::class, 'import'])->name('client.import');
    Route::get('client/export', [\App\Http\Controllers\Admin\ClientCrudController::class, 'export'])->name('client.export');
    Route::get('/client/autocomplete', [\App\Http\Controllers\Admin\ClientCrudController::class, 'autocomplete'])->name('client.autocomplete');

    Route::crud('user', \App\Http\Controllers\Admin\UserCrudController::class);
    Route::crud('location', \App\Http\Controllers\Admin\LocationCrudController::class);
    Route::crud('space', \App\Http\Controllers\Admin\SpaceCrudController::class);
    Route::get('space-capacity/{id}', function ($id) {
        $space = \App\Models\Space::select('id', 'capacity')->findOrFail($id);
        return response()->json(['capacity' => $space->capacity]);
    });
    Route::crud('zone', \App\Http\Controllers\Admin\ZoneCrudController::class);
    Route::crud('rate', \App\Http\Controllers\Admin\RateCrudController::class);
    Route::crud('form', \App\Http\Controllers\Admin\FormCrudController::class);
    Route::crud('form-field', \App\Http\Controllers\Admin\FormFieldCrudController::class);

    Route::crud('inscription', \App\Http\Controllers\Admin\InscriptionCrudController::class);
    Route::get('inscription/{inscription}/generate', [\App\Http\Controllers\Admin\InscriptionCrudController::class, 'generate'])->name('inscription.generate');
    Route::put('/inscription/update-price', [\App\Http\Controllers\Admin\InscriptionCrudController::class, 'updatePrice'])->name('inscription.update.price');

    Route::crud('custom-settings/brand', \App\Http\Controllers\Admin\SettingsBrandCrudController::class);
    Route::crud('custom-settings/advanced', \App\Http\Controllers\Admin\SettingsAdvancedCrudController::class);
    Route::crud('custom-settings/tpv', \App\Http\Controllers\Admin\SettingsTpvCrudController::class);

    Route::crud('validation', \App\Http\Controllers\Admin\ValidationController::class);
    Route::post('validation/check', [\App\Http\Controllers\Admin\ValidationController::class, 'check'])->name('validation.check');
    //Route::post('validation/out', [\App\Http\Controllers\Admin\ValidationController::class,'outEvent'])->name('validation.out');

    Route::crud('log-sync', \App\Http\Controllers\Admin\SyncValidationCrudController::class);

    Route::get('dashboard', [\App\Http\Controllers\Admin\AdminController::class, 'dashboard'])->name('backpack.dashboard');

    Route::get('/test-mailing', function () {
        $mailing = \App\Models\Mailing::find(289);
        $recipient = 'alexgragera05@gmail.com';

        $mailer = (new \App\Services\MailerBrandService('cirvianum'))->getMailer();
        $mailer->to($recipient)->send(new \App\Mail\MailingMail($mailing, 1));

        return 'Correo enviado a ' . $recipient;
    });

});

