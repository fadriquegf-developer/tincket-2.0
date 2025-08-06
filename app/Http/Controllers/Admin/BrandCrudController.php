<?php

namespace App\Http\Controllers\Admin;

use App\Models\Brand;
use App\Models\Capability;
use App\Http\Requests\BrandRequest;
use Backpack\CRUD\app\Library\Widget;
use App\Services\BrandCreationService;
use App\Traits\AllowUsersTrait;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class BrandCrudController extends CrudController
{   
    use AllowUsersTrait;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    protected BrandCreationService $brandCreator;

    public function __construct()
    {
        parent::__construct();
        $this->brandCreator = new BrandCreationService();
    }
    public function setup()
    {
        /* Acceso exclusivo para superusuarios */
        $this->isSuperuser();

        CRUD::setModel(Brand::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/brand');
        CRUD::setEntityNameStrings(__('backend.menu.brand'), __('backend.menu.brands'));
        CRUD::denyAccess('delete');
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.brand.name'),
            'type' => 'text'
        ]);

        CRUD::addColumn([
            'name' => 'code_name',
            'label' => __('backend.brand.code_name'),
            'type' => 'text'
        ]);

        CRUD::addColumn([
            'name' => 'allowed_host',
            'label' => __('backend.brand.allowed_host'),
            'type' => 'text'
        ]);

        CRUD::addColumn([
            'name' => 'capability',
            'label' => __('backend.brand.capability'),
            'type' => 'relationship',
            'attribute' => 'name',
        ]);

        CRUD::addColumn([
            'name' => 'parent',
            'label' => __('backend.brand.parent_id_list'),
            'type' => 'relationship',
            'attribute' => 'name',
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(BrandRequest::class);
        CRUD::setOperationSetting('showTranslationNotice', false);

        CRUD::addField([
            'name' => 'name',
            'label' => __('backend.brand.name'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'code_name',
            'label' => __('backend.brand.code_name'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'capability_id',
            'label' => __('backend.menu.capability'),
            'type' => 'select',
            'entity' => 'capability',
            'model' => Capability::class,
            'attribute' => 'name',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'parent_id',
            'label' => __('backend.brand.parent_id'),
            'type' => 'select',
            'entity' => 'parent',
            'model' => Brand::class,
            'attribute' => 'name',
            'options' => function ($query) {
                return $query->whereHas('capability', function ($q) {
                    $q->where('code_name', 'basic');
                })->get();
            },
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]

        ]);

        // Cargar JS personalizado solo en el formulario de Brand
        Widget::add()->type('script')->content('/assets/js/admin/forms/brand.js');
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function store(BrandRequest $request)
    {
        $this->brandCreator->withJavajanTpv()->create($request->validated());

        return redirect('/brand');
    }
}
