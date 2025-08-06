<?php

namespace App\Http\Controllers\Admin;

use DB;

use App\Models\Form;
use App\Models\Rate;
use App\Traits\CrudPermissionTrait;
use App\Http\Requests\RateCrudRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class RateCrudController extends CrudController
{

    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ReorderOperation;
    use CrudPermissionTrait;

    public function setup()
    {
        CRUD::setModel(Rate::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/rate');
        CRUD::setEntityNameStrings(__('backend.menu.rate'), __('backend.menu.rates'));


        CRUD::orderBy('lft');

        CRUD::allowAccess('reorder');
        CRUD::enableReorder('name', 1);

        CRUD::allowAccess(['show']);

        if (!\Auth::user()->hasRole('admin')) {
            $this->crud->removeButton('delete');
        }

        $this->setAccessUsingPermissions();
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.rate.name'),
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere(DB::raw('lower(name)'), 'like', '%' . strtolower($searchTerm) . '%');
            }
        ]);
    }

    protected function setupCreateOperation()
    {

        CRUD::setValidation(RateCrudRequest::class);

        CRUD::addField([
            'name' => 'name',
            'type' => 'text',
            'label' => __('backend.menu.rate'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            // select_from_array
            'name' => 'form_id',
            'label' => __('backend.rate.form'),
            'type' => 'select2_from_builder',
            'builder' => Form::orderBy('id', 'DESC'),
            'key' => 'id',
            'attribute' => 'name',
            'allows_null' => true,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        CRUD::addField([
            'name' => 'has_rule',
            'label' => __('backend.rate.has_rule'),
            'type' => 'switch',
            'wrapperAttributes' => [
                'class' => 'col-lg-6 form-group form-inline'
            ]
        ]);

        CRUD::addField([
            'name' => 'needs_code',
            'type' => 'switch',
            'label' => __('backend.rate.needs_code'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'rule_parameters',
            'label' => __('backend.rate.rule_parameters'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'validator_class',
            'type' => 'select_from_array',
            'options' => Rate::VALIDATOR_CLASSES,
            'label' => __('backend.rate.validator_class'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ]
        ]);
       
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
        
    }

}
