<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoginThrottleMiddleware
{
    /**
     * Límites de intentos de login
     */
    const MAX_ATTEMPTS = 5;
    const DECAY_MINUTES = 15;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar a rutas de login
        if (!$this->isLoginRoute($request)) {
            return $next($request);
        }

        $key = $this->resolveRequestSignature($request);

        // Verificar si está bloqueado
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            Log::warning('Login throttle activated', [
                'ip' => $request->ip(),
                'email' => $request->input('email'),
                'blocked_for' => $seconds . ' seconds'
            ]);

            return response()->json([
                'error' => __('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)])
            ], 429);
        }

        // Incrementar contador
        RateLimiter::hit($key, self::DECAY_MINUTES * 60);

        $response = $next($request);

        // Si el login fue exitoso, limpiar el contador
        if ($response->getStatusCode() === 200 && $this->isLoginSuccessful($request, $response)) {
            RateLimiter::clear($key);
        }

        return $response;
    }

    /**
     * Determinar si es una ruta de login
     */
    private function isLoginRoute(Request $request): bool
    {
        $loginRoutes = [
            'api/v1/client/search',  // Login de cliente API
            'admin/login',           // Login de admin
            'login',                 // Login general
            'backpack/login',        // Backpack login
        ];

        $path = $request->path();

        foreach ($loginRoutes as $route) {
            if (str_ends_with($path, $route) || $path === $route) {
                return true;
            }
        }

        // También verificar por nombre de ruta
        $routeName = $request->route()?->getName();
        $loginRouteNames = [
            'backpack.auth.login',
            'login',
            'api.client.search',
        ];

        return in_array($routeName, $loginRouteNames);
    }

    /**
     * Generar clave única para rate limiting
     */
    private function resolveRequestSignature(Request $request): string
    {
        $email = $request->input('email', '');
        $ip = $request->ip();

        // Combinar email e IP para evitar bloqueos globales
        return 'login_attempt:' . sha1($email . '|' . $ip);
    }

    /**
     * Verificar si el login fue exitoso
     */
    private function isLoginSuccessful(Request $request, Response $response): bool
    {
        // Para respuestas JSON
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            return isset($data['token']) ||
                isset($data['access_token']) ||
                isset($data['client']);
        }

        // Para respuestas de redirección (login web exitoso)
        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            return $response->getStatusCode() === 302 &&
                !$request->session()->has('errors');
        }

        return false;
    }
}
