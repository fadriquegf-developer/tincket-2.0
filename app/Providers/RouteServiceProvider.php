<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // 1. Rutas públicas y API (siempre se cargan)
        $this->loadRoutesFrom(base_path('routes/open.php'));
        $this->loadRoutesFrom(base_path('routes/console.php'));
        $this->loadRoutesFrom(base_path('routes/api.php'));
        $this->loadRoutesFrom(base_path('routes/api_backend.php'));
        $this->loadRoutesFrom(base_path('routes/api_validation.php'));

        // 2. Rutas compartidas base (todos tienen acceso)
        $this->loadRoutesFrom(base_path('routes/shared/base.php'));

        // 3. Rutas específicas por capability
        $brand = get_current_brand();
        if ($brand && $brand->capability) {
            switch ($brand->capability->code_name) {
                case 'engine':
                    // Engine tiene sus rutas exclusivas
                    $this->loadRoutesFrom(base_path('routes/capabilities/engine.php'));
                    break;

                case 'basic':
                    // Basic tiene acceso a CRM, eventos, carrito y sus exclusivas
                    $this->loadRoutesFrom(base_path('routes/shared/crm.php'));
                    $this->loadRoutesFrom(base_path('routes/shared/events.php'));
                    $this->loadRoutesFrom(base_path('routes/shared/cart.php'));
                    $this->loadRoutesFrom(base_path('routes/shared/clients.php'));
                    $this->loadRoutesFrom(base_path('routes/capabilities/basic.php'));
                    break;

                case 'promoter':
                    // Promoter tiene acceso a CRM, eventos, carrito y sus exclusivas
                    $this->loadRoutesFrom(base_path('routes/shared/crm.php'));
                    $this->loadRoutesFrom(base_path('routes/shared/events.php'));
                    $this->loadRoutesFrom(base_path('routes/shared/cart.php'));
                    $this->loadRoutesFrom(base_path('routes/shared/clients.php'));
                    $this->loadRoutesFrom(base_path('routes/capabilities/promoter.php'));
                    break;
            }
        }

        // 4. Rutas de superadmin (se cargan si el usuario es superadmin)
        $this->loadRoutesFrom(base_path('routes/superadmin.php'));

        // 5. Rutas de usuarios (condicionales por permisos)
        $this->loadRoutesFrom(base_path('routes/shared/users.php'));
    }
}
