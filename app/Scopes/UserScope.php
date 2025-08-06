<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class UserScope implements Scope
{
    /**
     * Aplica el scope global para filtrar por user_id en el modelo.
     *
     * @param  Builder  $builder
     * @param  Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (!app()->runningInConsole()) {
            // Verificamos que exista un usuario autenticado antes de aplicar el filtro
            $user = Auth::user();
            if ($user) {
                $builder->where($model->getTable() . '.user_id', $user->id);
            }
        }
    }
}
