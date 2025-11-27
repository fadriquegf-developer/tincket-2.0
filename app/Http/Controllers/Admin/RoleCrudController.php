<?php

namespace App\Http\Controllers\Admin;

use App\Models\Permission;
use App\Models\Role;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\PermissionManager\app\Http\Requests\RoleStoreCrudRequest as StoreRequest;
use Backpack\PermissionManager\app\Http\Requests\RoleUpdateCrudRequest as UpdateRequest;
use Spatie\Permission\PermissionRegistrar;
use App\Traits\CrudPermissionTrait;

class RoleCrudController extends CrudController
{
    use CrudPermissionTrait;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        $this->crud->setModel(Role::class);

        $this->crud->setEntityNameStrings(
            trans('backpack::permissionmanager.role'),
            trans('backpack::permissionmanager.roles')
        );
        $this->crud->setRoute(backpack_url('role'));

        /* Activamos el sistema de permisos */
        $this->setAccessUsingPermissions();

        // Aplicar filtro de brand AQUÍ, no en el modelo
        $this->applyBrandFilter();
    }

    /**
     * Aplica el filtro de brand para mostrar solo:
     * - Roles de la brand actual
     * - Roles generales (brand_id = null)
     */
    protected function applyBrandFilter()
    {
        $brandCapability = get_brand_capability();

        // Si es engine, mostrar todos los roles
        if ($brandCapability === 'engine') {
            // Engine ve todo
            return;
        }

        // Para otras brands, filtrar
        $brandId = get_current_brand_id();

        if ($brandId) {
            $this->crud->addClause('where', function ($query) use ($brandId) {
                $query->where('brand_id', $brandId)
                    ->orWhereNull('brand_id');
            });
        } else {
            // Si no hay brand actual, solo mostrar roles generales
            $this->crud->addClause('whereNull', 'brand_id');
        }
    }

    public function setupListOperation()
    {
        /**
         * Show a column for the name of the role.
         */
        $this->crud->addColumn([
            'name'  => 'name',
            'label' => trans('backpack::permissionmanager.name'),
            'type'  => 'text',
        ]);

        /**
         * Columna para mostrar si es un rol general o de brand específica
         */
        $this->crud->addColumn([
            'name'  => 'brand_scope',
            'label' => 'Ámbito',
            'type'  => 'model_function',
            'function_name' => 'getScopeLabel',
            'wrapper' => [
                'element' => 'span',
                'class' => function ($crud, $column, $entry, $related_key) {
                    return $entry->brand_id ? 'badge badge-info' : 'badge badge-success';
                },
            ],
        ]);

        /**
         * Show a column with the number of users that have that particular role.
         */
        $this->crud->query->withCount('users');
        $this->crud->addColumn([
            'label'     => trans('backpack::permissionmanager.users'),
            'type'      => 'text',
            'name'      => 'users_count',
            'wrapper'   => [
                'href' => function ($crud, $column, $entry, $related_key) {
                    return backpack_url('user?role=' . $entry->getKey());
                },
            ],
            'suffix'    => ' ' . strtolower(trans('backpack::permissionmanager.users')),
        ]);

        /**
         * Show the exact permissions that role has.
         */
        $this->crud->addColumn([
            'label'     => mb_ucfirst(trans('backpack::permissionmanager.permission_plural')),
            'type'      => 'select_multiple',
            'name'      => 'permissions',
            'entity'    => 'permissions',
            'attribute' => 'name',
            'model'     => Permission::class,
            'pivot'     => true,
        ]);
    }

    public function setupCreateOperation()
    {
        $this->addFields();
        $this->crud->setValidation(StoreRequest::class);

        // Limpiar caché de permisos
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function setupUpdateOperation()
    {
        $this->addFields();
        $this->crud->setValidation(UpdateRequest::class);

        // Limpiar caché de permisos
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function addFields()
    {
        $this->crud->addField([
            'name'  => 'name',
            'label' => trans('backpack::permissionmanager.name'),
            'type'  => 'text',
        ]);

        // Si NO es engine, asignar automáticamente el brand_id actual
        if (get_brand_capability() !== 'engine') {
            $this->crud->addField([
                'name'  => 'brand_id',
                'type'  => 'hidden',
                'value' => get_current_brand_id(),
            ]);
        } else {
            // Si es engine, puede elegir si crear rol general o para brand específica
            $this->crud->addField([
                'name'  => 'brand_id',
                'label' => 'Brand (dejar vacío para rol general)',
                'type'  => 'select2',
                'entity' => 'brand',
                'attribute' => 'name',
                'model' => "App\Models\Brand",
                'allows_null' => true,
                'placeholder' => 'Rol general (todas las brands)',
            ]);
        }

        if (config('backpack.permissionmanager.multiple_guards')) {
            $this->crud->addField([
                'name'    => 'guard_name',
                'label'   => trans('backpack::permissionmanager.guard_type'),
                'type'    => 'select_from_array',
                'options' => $this->getGuardTypes(),
            ]);
        }

        $this->crud->addField([
            'label'     => mb_ucfirst(trans('backpack::permissionmanager.permission_plural')),
            'type'      => 'permissions_grouped_checklist',
            'name'      => 'permissions',
            'entity'    => 'permissions',
            'attribute' => 'name',
            'model'     => Permission::class,
            'pivot'     => true,
        ]);
    }

    private function getGuardTypes()
    {
        $guards = config('auth.guards');
        $returnable = [];
        foreach ($guards as $key => $details) {
            $returnable[$key] = $key;
        }
        return $returnable;
    }
}
