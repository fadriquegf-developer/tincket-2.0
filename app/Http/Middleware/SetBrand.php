<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SetBrand
{
    /**
     * Maneja la solicitud entrante asignando la información de la marca actual a la request.
     *
     * @param  Request  $request
     * @param  \Closure(Request): Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        $brand = get_current_brand();
        if (!$brand) {
            $brand = request()->get('brand');
        }


        if (
            !$brand &&
            $request->has('token') &&
            $request->routeIs('open.inscription.pdf') || $request->routeIs('public.inscription.show')
        ) {
            return $next($request); // Deja que el controlador cargue la brand manualmente
        }


        if (!$brand) {
            abort(403, 'Unknown brand.');
        }

        // Asignamos la marca completa y algunos atributos clave en la request.
        $request->attributes->set('brand', $brand);
        $request->attributes->set('brand.id', $brand->id);
        $request->attributes->set('brand.code_name', $brand->code_name);
        $request->attributes->set('brand.capability', $brand->capability?->code_name);

        return $next($request);
    }
}
