<?php

namespace App\Http\Controllers\Admin;


use App\Models\Taxonomy;
use App\Traits\CrudPermissionTrait;
use App\Http\Requests\TaxonomyCrudRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class TaxonomyCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ReorderOperation;
    use CrudPermissionTrait;

    public function setup()
    {
        CRUD::setModel(Taxonomy::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/taxonomy');
        CRUD::setEntityNameStrings(__('backend.menu.taxonomy'), __('backend.menu.taxonomies'));

        $this->setAccessUsingPermissions();
        CRUD::allowAccess('reorder');
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'visibility',
            'label' => '',
            'type' => 'closure',
            'function' => function ($entry) {
                if ($entry->active == 1) {
                    return '<i class="la la-circle" title="' . __('backend.session.visibility') . '" aria-hidden="true" style="color:green;"></i>';
                } else {
                    return '<i class="la la-circle" title="' . __('backend.session.no-visibility') . '" aria-hidden="true" style="color:red;"></i>';
                }
            },
            'escaped' => false,
        ]);

        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.taxonomy.name'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'slug',
            'label' => __('backend.taxonomy.slug'),
            'type' => 'text',
        ]);
    }

    protected function setupReorderOperation()
    {
        CRUD::set('reorder.label', 'name');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(TaxonomyCrudRequest::class);

        CRUD::addField([
            'name' => 'name',
            'type' => 'text',
            'label' => __('backend.taxonomy.name'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6 required'
            ],
            'required' => true,
        ]);

        CRUD::addField([
            'name' => 'slug',
            'type' => 'slug',
            'target' => 'name',
            'label' => __('backend.taxonomy.slug'),
            'wrapperAttributes' => [
                'class' => 'col-lg-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'active',
            'label' => __('backend.taxonomy.active'),
            'type' => 'switch',
            'wrapperAttributes' => [
                'class' => 'mt-2'
            ],
            'default' => false,
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
