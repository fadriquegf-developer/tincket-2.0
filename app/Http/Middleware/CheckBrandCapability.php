<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckBrandCapability
{
    /**
     * Maneja la petición entrante.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  string   $capability  
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $capability)
    {
        // Se puede obtener la marca previamente inyectada o con get_current_brand()
        $brand = get_current_brand();

        // Usamos el operador nullsafe para obtener de forma concisa el code_name
        if ($brand?->capability?->code_name !== $capability) {
            abort(403, 'Acceso no autorizado');
        }

        return $next($request);
    }
}
