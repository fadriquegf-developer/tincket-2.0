<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class CheckIfAdmin
{
    /**
     * Verificación real de permisos de administrador
     */
    private function checkIfUserIsAdmin($user): bool
    {
        if (!$user) {
            return false;
        }

        // Verificar múltiples condiciones
        $checks = [
            'is_superadmin' => in_array($user->id, config('superusers.ids', [1])),
            'has_admin_role' => $user->hasRole(['admin', 'super-admin']),
            'has_brand_admin' => $this->checkBrandAdmin($user),
            'is_active' => $user->email_verified_at !== null,
        ];

        // Requiere al menos uno de los primeros 3 checks Y estar activo
        return $checks['is_active'] && (
            $checks['is_superadmin'] ||
            $checks['has_admin_role'] ||
            $checks['has_brand_admin']
        );
    }

    /**
     * Verificar si es admin de la brand actual
     */
    private function checkBrandAdmin($user): bool
    {
        $brand = get_current_brand();

        if (!$brand) {
            return false;
        }

        // Verificar que el usuario pertenece a la brand
        if (!$brand->users()->where('user_id', $user->id)->exists()) {
            return false;
        }

        // Verificar si tiene roles de admin para esta brand
        return $user->roles()
            ->forCurrentBrand() // Usa el scope que ya tienes en Role.php
            ->whereIn('name', ['admin'])
            ->exists();
    }

    /**
     * Respuesta a acceso no autorizado
     */
    private function respondToUnauthorizedRequest($request)
    {
        // Log del intento
        Log::warning('Unauthorized admin access attempt', [
            'ip' => $request->ip(),
            'path' => $request->path(),
            'user_id' => backpack_user()?->id
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'error' => trans('backpack::base.unauthorized'),
                'message' => 'Admin privileges required'
            ], 401);
        }

        return redirect()->guest(backpack_url('login'))
            ->with('error', 'Admin privileges required');
    }

    /**
     * Handle con validaciones mejoradas
     */
    public function handle($request, Closure $next)
    {
        // Verificar si hay sesión válida
        if (backpack_auth()->guest()) {
            return $this->respondToUnauthorizedRequest($request);
        }

        $user = backpack_user();

        // Verificar que el usuario existe y está activo
        if (!$user || $user->deleted_at !== null) {
            backpack_auth()->logout();
            return $this->respondToUnauthorizedRequest($request);
        }

        // Verificar permisos de admin
        if (!$this->checkIfUserIsAdmin($user)) {
            return $this->respondToUnauthorizedRequest($request);
        }

        // Verificar acceso a la brand actual
        if (!$this->hasAccessToCurrentBrand($user)) {
            return $this->respondToUnauthorizedRequest($request);
        }

        // Verificar sesión no ha expirado por inactividad
        if ($this->sessionExpiredByInactivity($request)) {
            backpack_auth()->logout();
            return redirect()->guest(backpack_url('login'))
                ->with('error', 'Session expired due to inactivity');
        }

        // Actualizar última actividad
        $request->session()->put('last_activity', now());

        return $next($request);
    }

    /**
     * Verificar acceso a la brand actual
     */
    private function hasAccessToCurrentBrand($user): bool
    {
        // Superadmins tienen acceso a todo
        if (in_array($user->id, config('superusers.ids', [1]))) {
            return true;
        }

        $brand = get_current_brand();
        if (!$brand) {
            return false;
        }

        // Verificar que el usuario pertenece a esta brand
        return $brand->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Verificar timeout por inactividad (30 minutos)
     */
    private function sessionExpiredByInactivity($request): bool
    {
        $lastActivity = $request->session()->get('last_activity');

        if (!$lastActivity) {
            return false;
        }

        $maxInactivity = config('session.lifetime', 120) * 60; // convertir a segundos

        return now()->diffInSeconds($lastActivity) > $maxInactivity;
    }
}
