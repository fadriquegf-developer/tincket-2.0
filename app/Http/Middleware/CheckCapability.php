<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckCapability
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $capability
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $capability)
    {
        $brand = get_current_brand();

        if (!$brand || !$brand->capability) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        // Verificar si la capability de la brand coincide
        if ($brand->capability->code_name !== $capability) {
            abort(403, 'Tu tipo de cuenta no tiene acceso a esta funcionalidad.');
        }

        return $next($request);
    }
}
