<?php

namespace App\Traits;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
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

        if (!Auth::check()) {
            throw new AuthorizationException('Usuario no autenticado.');
        }

        if (!in_array(Auth::id(), $ids)) {
            abort(403, 'No tienes acceso a este recurso.');
        }
    }

    /**
     * Determina si el usuario actual es un superusuario (por ID).
     */
    public function isSuperuser(): bool
    {
        $superuserIds = config('superusers.ids');
        return Auth::check() && in_array(Auth::id(), $superuserIds);
    }
}
