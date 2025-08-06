<?php

namespace App\Http\Controllers\Admin;

use App\Models\Brand;
use Illuminate\Support\Str;
use App\Traits\AllowUsersTrait;
use App\Traits\CrudPermissionTrait;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class CapabilityCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ApplicationCrudController extends CrudController
{
    use AllowUsersTrait;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        /* Acceso exclusivo para superusuarios */
        $this->isSuperuser();

        CRUD::setModel(\App\Models\Application::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/application');
        CRUD::setEntityNameStrings(__('backend.menu.application'), __('backend.menu.applications'));
        CRUD::denyAccess('delete');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'code_name',
            'label' => __('backend.application.code_name'),
            'type'=> 'text',
        ]);

        CRUD::addColumn([
            'name'=> 'key',
            'label' => __('backend.application.key'),
            'type'=> 'text',
        ]);

        CRUD::addColumn([
            'name' => 'brand_id',
            'label' => __('backend.application.brand_id'),
            'type' => 'select',
            'entity' => 'brand',
            'attribute' => 'name',
            'model' => Brand::class
        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {

        CRUD::addField([
            'name' => 'code_name',
            'label'=> __('backend.application.code_name'),
            'type' => 'text',
            'default' => 'Web API',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'brand_id',
            'type' => 'select2',
            'label' => __('backend.application.brand_id'),
            'entity' => 'brand',
            'attribute' => 'name',
            'model' => Brand::class,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'key',
            'type' => 'hidden',
            'value' => Str::random(32),
        ]);


    }


}
