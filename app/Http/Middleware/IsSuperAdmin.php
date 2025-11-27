<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Verificar si el usuario es superadmin
        $superadminIds = config('superusers.ids', []);

        if (!backpack_user() || !in_array(backpack_user()->id, $superadminIds)) {
            abort(403, 'Solo los super administradores tienen acceso a esta sección.');
        }

        return $next($request);
    }
}
