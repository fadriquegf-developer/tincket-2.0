<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Application;

class CheckApiToken
{
    /**
     * Maneja la solicitud entrante validando el token.
     *
     * @param  Request  $request
     * @param  Closure(Request) $next
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-TK-APPLICATION-KEY');

        if ($this->validateToken($token, $request)) {
            return $next($request);
        }

        abort(403, 'Permission denied.');
    }

    /**
     * Valida que el token proporcionado corresponda a una aplicación pública
     * que pertenezca al brand indicado en la request.
     *
     * @param  string|null  $token
     * @param  Request  $request
     * @return bool
     */
    private function validateToken(?string $token, Request $request): bool
    {
        // Obtenemos el brand id de la request, previamente inyectado por otro middleware.
        $brandId = get_current_brand()->id;

        // Se busca la aplicación con code_name "public_api" asociada al brand y token dados
        $application = Application::where('code_name', 'public_api')
            ->where('brand_id', $brandId)
            ->where('key', $token)
            ->first();

        return $application !== null;
    }
}
