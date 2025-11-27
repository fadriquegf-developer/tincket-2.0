<?php

namespace App\Traits;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

trait CrudPermissionTrait
{
    /**
     * Mapeo de sufijos de permisos a operaciones CRUD.
     */
    protected array $permissionOperationMapping = [
        'index'  => ['list'],
        'show'   => ['show'],
        'create' => ['create'],
        'edit'   => ['update'],
        'delete' => ['delete'],
    ];

    /**
     * Configura el acceso a las operaciones CRUD basado en los permisos del usuario.
     */
    public function setAccessUsingPermissions()
    {
        // Denegar TODAS las operaciones por defecto
        $this->crud->denyAccess(['list', 'show', 'create', 'update', 'delete']);

        // Obtener el usuario autenticado
        $user = request()->user() ?? backpack_user();

        if (!$user) {
            // CRÍTICO: Si no hay usuario, mantener todo denegado y salir
            \Log::warning('[Permission] Access denied - No user authenticated for ' . request()->url());
            return;
        }

        // Determinar qué nombre usar para los permisos
        // Comprovar si la classe que usa el trait té la propietat $customPermissionName
        if (property_exists($this, 'customPermissionName') && $this->customPermissionName) {
            $table = $this->customPermissionName;
        } else {
            // Usar el nombre de la tabla del modelo por defecto
            $model = CRUD::getModel();
            $table = is_string($model) ? (new $model)->getTable() : $model->getTable();
        }

        // Para usuarios engine (superadmin), dar todos los permisos
        if (get_brand_capability() === 'engine' && in_array($user->id, config('superusers.ids', [1]))) {
            $this->crud->allowAccess(['list', 'show', 'create', 'update', 'delete']);
            return;
        }

        // Iterar sobre cada mapeo de permiso
        foreach ($this->permissionOperationMapping as $suffix => $operations) {
            $permission = "$table.$suffix";

            try {
                if ($user->hasPermissionTo($permission)) {
                    $this->crud->allowAccess($operations);
                }
            } catch (\Exception $e) {
                \Log::error("[Permission] Error checking permission $permission: " . $e->getMessage());
                // En caso de error, mantener el acceso denegado
            }
        }
    }
}
