<?php

namespace App\Traits;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait AllowUsersTrait
{
    /**
     * Verifica si el usuario actual está entre los IDs permitidos.
     *
     * @param int|array $ids Puede ser un ID de usuario o un array de IDs.
     * @throws AuthorizationException|HttpException
     */
    public function allowOnlyUsers(int|array $ids): void
    {
        if (is_int($ids)) {
            $ids = [$ids];
        }

        if (!is_array($ids)) {
            throw new \InvalidArgumentException('El parámetro $ids debe ser un entero o un array de enteros.');
        }

        $user = $this->getCurrentAuthenticatedUser();

        if (!$user) {
            Log::warning('AllowUsersTrait: Intento de acceso sin autenticación en ' . request()->url());
            abort(401, 'Autenticación requerida.');
        }

        if (!in_array($user->id, $ids, true)) {
            abort(403, 'No tienes acceso a este recurso.');
        }
    }

    /**
     * Requiere que el usuario sea superusuario o aborta
     * 
     * @throws HttpException
     * @return void
     */
    public function requireSuperuser(): void
    {
        if (!$this->isSuperuser()) {
            $userId = $this->getCurrentAuthenticatedUser()?->id ?? 'no-auth';
            Log::warning('AllowUsersTrait: Intento de acceso a recurso de superusuario por usuario ID ' . $userId);
            abort(403, 'Esta función requiere privilegios de superusuario.');
        }
    }

    /**
     * Determina si el usuario actual es un superusuario
     * 
     * @return bool
     */
    public function isSuperuser(): bool
    {
        $user = $this->getCurrentAuthenticatedUser();

        if (!$user) {
            return false;
        }

        $superuserIds = config('superusers.ids', []);

        // Validar que la configuración es correcta
        if (!is_array($superuserIds) || empty($superuserIds)) {
            Log::error('AllowUsersTrait: Configuración de superusers.ids inválida o vacía');
            return false;
        }

        return in_array($user->id, $superuserIds, true);
    }

    /**
     * Verifica si el usuario tiene una capability específica
     * 
     * @param string $capability
     * @return bool
     */
    public function hasCapability(string $capability): bool
    {
        $currentCapability = get_brand_capability();

        if (!$currentCapability) {
            return false;
        }

        // Engine tiene todas las capabilities
        if ($currentCapability === 'engine') {
            return true;
        }

        return $currentCapability === $capability;
    }

    /**
     * Requiere una capability específica o aborta
     * 
     * @param string $capability
     * @throws HttpException
     * @return void
     */
    public function requireCapability(string $capability): void
    {
        if (!$this->hasCapability($capability)) {
            abort(403, 'Esta función requiere capability: ' . $capability);
        }
    }

    /**
     * Verifica si el usuario actual pertenece a una brand específica
     * 
     * @param int $brandId
     * @return bool
     */
    public function belongsToBrand(int $brandId): bool
    {
        $user = $this->getCurrentAuthenticatedUser();

        if (!$user) {
            return false;
        }

        // Si el usuario tiene relación con brands
        if (method_exists($user, 'brands')) {
            return $user->brands()->where('brands.id', $brandId)->exists();
        }

        // Si el usuario tiene brand_id directo
        if (isset($user->brand_id)) {
            return $user->brand_id === $brandId;
        }

        return false;
    }

    /**
     * Requiere que el usuario pertenezca a una brand específica
     * 
     * @param int $brandId
     * @throws HttpException
     * @return void
     */
    public function requireBrand(int $brandId): void
    {
        if (!$this->belongsToBrand($brandId)) {
            $user = $this->getCurrentAuthenticatedUser();
            Log::warning('AllowUsersTrait: Usuario ' . ($user?->id ?? 'no-auth') . ' intentó acceder a recurso de brand ' . $brandId);
            abort(403, 'No tienes acceso a recursos de esta marca.');
        }
    }

    /**
     * Verifica si el usuario puede acceder desde la IP actual
     * 
     * @return bool
     */
    public function canAccessFromCurrentIp(): bool
    {
        $user = $this->getCurrentAuthenticatedUser();

        if (!$user) {
            return false;
        }

        // Si el modelo tiene el método canAccessFromIp
        if (method_exists($user, 'canAccessFromIp')) {
            return $user->canAccessFromIp(request()->ip());
        }

        return true; // Si no tiene restricciones de IP, permitir
    }

    /**
     * Requiere que el usuario pueda acceder desde la IP actual
     * 
     * @throws HttpException
     * @return void
     */
    public function requireValidIp(): void
    {
        if (!$this->canAccessFromCurrentIp()) {
            $user = $this->getCurrentAuthenticatedUser();
            $ip = request()->ip();
            Log::warning('AllowUsersTrait: Acceso denegado desde IP ' . $ip . ' para usuario ' . ($user?->id ?? 'unknown'));
            abort(403, 'Acceso no permitido desde esta dirección IP.');
        }
    }

    /**
     * Obtiene el usuario autenticado actual
     * Centralizado para facilitar testing y mantener consistencia
     * 
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function getCurrentAuthenticatedUser()
    {
        // Primero intentar con backpack_user si está disponible
        if (function_exists('backpack_user')) {
            $user = backpack_user();
            if ($user) {
                return $user;
            }
        }

        // Fallback a Auth facade
        return Auth::user();
    }

    /**
     * Verifica múltiples condiciones de acceso a la vez
     * 
     * @param array $conditions Array con las condiciones a verificar
     * @throws HttpException
     * @return bool
     * 
     * Ejemplo:
     * $this->requireAllConditions([
     *     'superuser' => true,
     *     'capability' => 'engine',
     *     'brand' => 5,
     *     'valid_ip' => true
     * ]);
     */
    public function requireAllConditions(array $conditions): bool
    {
        foreach ($conditions as $condition => $value) {
            switch ($condition) {
                case 'superuser':
                    if ($value && !$this->isSuperuser()) {
                        $this->requireSuperuser(); // Lanzará excepción
                    }
                    break;

                case 'capability':
                    if ($value && !$this->hasCapability($value)) {
                        $this->requireCapability($value); // Lanzará excepción
                    }
                    break;

                case 'brand':
                    if ($value && !$this->belongsToBrand($value)) {
                        $this->requireBrand($value); // Lanzará excepción
                    }
                    break;

                case 'valid_ip':
                    if ($value && !$this->canAccessFromCurrentIp()) {
                        $this->requireValidIp(); // Lanzará excepción
                    }
                    break;

                case 'users':
                    if (is_array($value)) {
                        $this->allowOnlyUsers($value); // Lanzará excepción si no cumple
                    }
                    break;

                default:
                    Log::warning('AllowUsersTrait: Condición desconocida: ' . $condition);
            }
        }

        return true;
    }
}
