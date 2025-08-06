<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // Cargamos las rutas generales usadas por todas las brands
        $this->loadRoutesFrom(base_path('routes/web.php'));
        $this->loadRoutesFrom(base_path('routes/core.php'));
        $this->loadRoutesFrom(base_path('routes/open.php'));
        $this->loadRoutesFrom(base_path('routes/console.php'));
        $this->loadRoutesFrom(base_path('routes/api.php'));
        $this->loadRoutesFrom(base_path('routes/api_backend.php'));

        // Cargamos rutas exclusivas de cada brand dependiendo del tipo de cliente
        $brand = get_current_brand();
        if ($brand && $brand->capability) {
            switch ($brand->capability->code_name) {
                case 'engine':
                    $this->loadRoutesFrom(base_path('routes/engine.php'));
                    break;
                case 'basic':
                    $this->loadRoutesFrom(base_path('routes/basic.php'));
                    break;
                case 'promoter':
                    $this->loadRoutesFrom(base_path('routes/promoter.php'));
                    break;
                default:
                    break;
            }
        }
    }
}
