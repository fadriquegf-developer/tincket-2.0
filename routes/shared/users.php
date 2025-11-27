<?php

use Illuminate\Support\Facades\Route;

// ==========================================
// Gestión de usuarios (condicional por permisos)
// ==========================================

Route::middleware(['web', 'auth:backpack'])->group(function () {
    // Usuarios - acceso condicional por permisos
    Route::crud('user', \App\Http\Controllers\Admin\UserCrudController::class);
});
