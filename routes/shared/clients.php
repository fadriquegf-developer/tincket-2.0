<?php

use Illuminate\Support\Facades\Route;

// ==========================================
// Gestión de clientes (condicional por permisos)
// ==========================================

Route::middleware(['web', 'auth:backpack'])->group(function () {
    // Clientes - todos necesitan acceso básico a clientes
    Route::crud('client', \App\Http\Controllers\Admin\ClientCrudController::class);
});
