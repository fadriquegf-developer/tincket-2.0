<?php

namespace App\Traits;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

trait CrudPermissionTrait
{
    /**
     * Mapeo de sufijos de permisos a operaciones CRUD.
     *
     * Por ejemplo, el permiso "users.index" permite la operación "list",
     * "users.show" permite la operación "show", "users.create" la operación "create", etc.
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
     *
     */
    public function setAccessUsingPermissions()
    {
        // Obtén el modelo y la tabla que se está utilizando en el CRUD.
        $model = CRUD::getModel();
        $table = is_string($model) ? (new $model)->getTable() : $model->getTable();

        // Obtén el usuario autenticado.
        $user = request()->user();

        if (!$user) {
            //\Log::debug('[Permission] No user authenticated');
            return;
        }

        // Deniega inicialmente todas las operaciones para partir de una base segura.
        $this->crud->denyAccess('list', 'show', 'create', 'update', 'delete');

        // Itera sobre cada mapeo de permiso.
        foreach ($this->permissionOperationMapping as $suffix => $operations) {
            // Construye el permiso; por ejemplo: "users.index", "users.show", etc.
            $permission = "$table.$suffix";

            // Verifica si el usuario tiene el permiso usando el método hasPermissionTo().
            if ($user->hasPermissionTo($permission)) {
                //\Log::debug("[Permission] Granting access to: $permission");
                $this->crud->allowAccess($operations);
            } else {
                //\Log::debug("[Permission] Denying access to: $permission");
                $this->crud->denyAccess($operations);
            }
        }
    }
}
