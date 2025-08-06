<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserRequest;
use App\Traits\CrudPermissionTrait;
use Illuminate\Support\Facades\Hash;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

/**
 * Class UserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    use CrudPermissionTrait;
    use ListOperation;
    use CreateOperation {
        store as traitStore;
    }
    use UpdateOperation {
        update as traitUpdate;
    }
    use DeleteOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user');
        CRUD::setEntityNameStrings(__('backend.menu.user'), __('backend.menu.users'));

        // Activar sistema de permisos
        $this->setAccessUsingPermissions();

        if (get_brand_capability() === 'engine') {
            CRUD::denyAccess('create');
        }
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $brandId = optional(get_current_brand())->id;

        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.user.name'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'email',
            'label' => __('backend.user.email'),
            'type' => 'email',
        ]);

        // n-n relationship (with pivot table)
        CRUD::addColumn([
            'label' => trans('backpack::permissionmanager.roles'),
            'type' => 'select_multiple',
            'name' => 'roles',
            'entity' => 'roles',
            'attribute' => 'name',
            'model' => config('permission.models.role'),
        ]);


        //No funciona bien y corta la columna brand
        /* CRUD::addColumn([
            'label' => trans('backpack::permissionmanager.extra_permissions'),
            'type' => 'select_multiple',
            'name' => 'permissions',
            'entity' => 'permissions',
            'attribute' => 'name',
            'model' => config('permission.models.permission'),
        ]); */

        if (get_brand_capability() === 'engine') {
            CRUD::addColumn([
                'label' => __('backend.user.brand'),
                'type' => 'model_function',
                'function_name' => 'getBrandsList'
            ]);
            // 🔽 Filtro por marca, solo visible para "engine"
            CRUD::addFilter(
                [
                    'name'  => 'brand_id',
                    'type'  => 'dropdown', // pon 'select2' si hay muchas marcas
                    'label' => __('backend.user.brand'),
                ],
                // values del dropdown
                function () {
                    return \App\Models\Brand::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray();
                },
                // lógica cuando el filtro está activo
                function ($value) {
                    $this->crud->addClause('whereHas', 'brands', function ($q) use ($value) {
                        $q->where('brands.id', $value);
                    });
                }
            );
        } else {
            CRUD::addClause('whereHas', 'brands', function ($query) use ($brandId) {
                $query->where('brands.id', $brandId);
            });
            // excluimos al usuario con id 1
            CRUD::addClause('where', 'id', '!=', 1);
        }



        CRUD::addFilter(
            [
                'name'  => 'role',
                'type'  => 'dropdown', // o 'select2' si hay muchos
                'label' => trans('backpack::permissionmanager.role'),
            ],
            // values: array o closure que retorne array id => label
            function () use ($brandId) {
                return config('permission.models.role')::query()
                    ->where(function ($q) use ($brandId) {
                        $q->where('brand_id', $brandId)
                            ->orWhereNull('brand_id');
                    })
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();
            },
            // lógica del filtro cuando está activo
            function ($value) {
                $this->crud->addClause('whereHas', 'roles', function ($q) use ($value) {
                    // Mejor apuntar al id de la tabla roles:
                    $q->where('roles.id', $value);
                });
            }
        );

        // Extra Permission Filter
        CRUD::addFilter(
            [
                'name' => 'permissions',
                'type' => 'select2',
                'label' => trans('backpack::permissionmanager.extra_permissions'),
            ],
            config('permission.models.permission')::all()->pluck('name', 'id')->toArray(),
            function ($value) { // if the filter is active
                $this->crud->addClause('whereHas', 'permissions', function ($query) use ($value) {
                    $query->where('permission_id', '=', $value);
                });
            }
        );
    }


    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    public function setupCreateOperation()
    {
        $this->crud->setValidation(UserRequest::class);
        $this->addUserFields();
        
    }

    public function store()
    {
        $this->crud->setRequest($this->crud->validateRequest());
        $this->crud->setRequest($this->handlePasswordInput($this->crud->getRequest()));
        $this->crud->unsetValidation(); // validation has already been run

        return $this->traitStore();
    }


    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function update()
    {
        $this->crud->setRequest($this->crud->validateRequest());
        $this->crud->setRequest($this->handlePasswordInput($this->crud->getRequest()));
        $this->crud->unsetValidation(); // validation has already been run

        return $this->traitUpdate();
    }

    protected function handlePasswordInput($request)
    {
        // Remove fields not present on the user.
        $request->request->remove('password_confirmation');
        $request->request->remove('roles_show');
        $request->request->remove('permissions_show');

        // Encrypt password if specified.
        if ($request->input('password')) {
            $request->request->set('password', Hash::make($request->input('password')));
        } else {
            $request->request->remove('password');
        }

        return $request;
    }

    protected function addUserFields()
    {
        $brandId = optional(get_current_brand())->id;

        CRUD::addField([
            'name' => 'name',
            'label' => __('backend.user.name'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'email',
            'label' => __('backend.user.email'),
            'type' => 'email',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'password',
            'label' => __('backend.user.password'),
            'type' => 'password',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'password_confirmation',
            'label' => __('backend.user.password_confirmation'),
            'type' => 'password',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'allowed_ips',
            'label' => 'Allowed ips',
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        // two interconnected entities
        CRUD::addField([
            'label' => trans('backpack::permissionmanager.user_role_permission'),
            'field_unique_name' => 'user_role_permission',
            'type' => 'grouped_checklist_dependency',
            'name' => 'roles,permissions',
            'subfields' => [
                'primary' => [
                    'label' => trans('backpack::permissionmanager.roles'),
                    'name' => 'roles',
                    'entity' => 'roles',
                    'entity_secondary' => 'permissions',
                    'attribute' => 'name',
                    'model' => config('permission.models.role'),
                    'pivot' => true,
                    'number_columns' => 3,
                    'options' => function ($query) use ($brandId) {
                        return $query
                            ->where(function ($q) use ($brandId) {
                                $q->where('brand_id', $brandId)
                                    ->orWhereNull('brand_id');
                            })
                            ->orderBy('name');
                    },
                ],
                'secondary' => [
                    'label' => mb_ucfirst(trans('backpack::permissionmanager.permission_plural')),
                    'name' => 'permissions',
                    'entity' => 'permissions',
                    'entity_primary' => 'roles',
                    'attribute' => 'name',
                    'model' => config('permission.models.permission'),
                    'pivot' => true,
                    'number_columns' => 3,
                ],
            ],
        ]);
    }
}
