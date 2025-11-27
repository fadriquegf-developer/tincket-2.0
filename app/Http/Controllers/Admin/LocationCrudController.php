<?php

namespace App\Http\Controllers\Admin;

use App\Models\City;
use App\Models\User;
use DB;
use App\Models\Space;
use App\Models\Region;
use App\Models\Location;
use App\Traits\CrudPermissionTrait;
use App\Http\Requests\LocationCrudRequest;
use Backpack\CRUD\app\Http\Controllers\Operations\InlineCreateOperation;
use Backpack\Pro\Http\Controllers\Operations\FetchOperation;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class LocationCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use InlineCreateOperation;
    use FetchOperation;
    use CrudPermissionTrait;

    public function setup()
    {
        CRUD::setModel(Location::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/location');
        CRUD::setEntityNameStrings(__('menu.location'), __('menu.locations'));

        //$this->crud->addButton('line', 'add_space', 'view', 'crud::buttons.add_space', 'beginning');
        $this->setAccessUsingPermissions();
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.location.locationname'),
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere(DB::raw('lower(name)'), 'like', '%' . strtolower($searchTerm) . '%');
            }
        ]);
        CRUD::addColumn([
            'label' => __('backend.location.city'),
            'type' => 'select',
            'name' => 'city_id',
            'entity' => 'city',
            'attribute' => 'name',
            'model' => City::class,
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('town', function ($q) use ($column, $searchTerm) {
                    $q->where(DB::raw('lower(name)'), 'like', '%' . strtolower($searchTerm) . '%');
                });
            }
        ]);

        CRUD::addColumn([
            'name' => 'postal_code',
            'label' => __('backend.location.postalcode')
        ]);

        CRUD::addColumn([
            'label' => __('backend.location.createdby'),
            'type' => 'select',
            'name' => 'user_id',
            'entity' => 'user',
            'attribute' => 'email',
            'model' => User::class,
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(LocationCrudRequest::class);

        CRUD::addField([
            'name' => 'name',
            'label' => __('backend.location.name'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'address',
            'label' => __('backend.location.address'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'postal_code',
            'label' => __('backend.location.postalcode'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'label'     => __('backend.location.city'),
            'type'      => 'select2_grouped',
            'name'      => 'city_id', 
            'entity'    => 'city', 
            'attribute' => 'name', 
            'model'     => City::class, 
        
            // Agrupación
            'group_by'                => 'region', 
            'group_by_attribute'      => 'name', 
            'group_by_relationship_back' => 'cities',
        
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ],
            'allows_null' => true,
        ]);
        
        CRUD::addField([
            'name' => 'phone1',
            'label' => __('backend.location.phone') . ' 1',
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'phone2',
            'label' => __('backend.location.extraphone'),
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'email',
            'label' => __('backend.location.email'),
            'type' => 'email',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => __('backend.location.description'),
            'type' => 'textarea',
            'wrapperAttributes' => [],
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

}
