<?php

namespace App\Http\Controllers\Admin;

use App\Models\Space;
use App\Http\Requests\ZoneRequest;
use App\Traits\CrudPermissionTrait;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ZoneCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ZoneCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use CrudPermissionTrait;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Zone::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/zone');
        CRUD::setEntityNameStrings(__('backend.menu.zone'), __('backend.menu.zones'));
        $this->setAccessUsingPermissions();
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $brandId = get_current_brand()->id; // o el método que uses para obtener brand actual

        $this->crud->addClause('whereHas', 'space', function ($query) use ($brandId) {
            $query->where('brand_id', $brandId);
        });

        CRUD::addColumn([
            'name' => 'space',
            'label' => __('backend.zone.space'),
            'type' => 'relationship',
            'attribute' => 'name',
            'model' => Space::class,
        ]);

        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.zone.name'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'color',
            'label' => __('backend.zone.color'),
            'type' => 'custom_html',
            'value' => function ($entry) {
                $c = e($entry->color);
                return "<span style=\"
                    display:inline-block;
                    width:1.5rem;
                    height:1.5rem;
                    border-radius:50%;
                    background:{$c};
                    border:1px solid #999;
                \"></span>";
            },
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
        CRUD::setValidation(ZoneRequest::class);

        CRUD::addField([
            'label' => __('backend.zone.space'),
            'type' => 'select2',
            'name' => 'space_id',
            'entity' => 'space',
            'attribute' => 'name',
            'model' => Space::class,
            'wrapper' => ['class' => 'form-group col-md-4 required'],
        ]);

        CRUD::addField([
            'name' => 'name',
            'label' => __('backend.zone.name'),
            'type' => 'text',
            'translatable' => true,
            'wrapper' => ['class' => 'form-group col-md-4 required'],
        ]);

        CRUD::addField([
            'name' => 'color',
            'label' => __('backend.zone.color'),
            'type' => 'color',
            'wrapper' => ['class' => 'form-group col-md-4 required'],
        ]);

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
}
