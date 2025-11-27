<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\RegisterInputRequest;
use Illuminate\Http\Request;
use App\Models\RegisterInput;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class RegisterInputCrudController extends CrudController
{

    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(RegisterInput::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/register-input');
        CRUD::setEntityNameStrings(__('menu.input'), __('menu.inputs'));
    }


    protected function setupListOperation()
    {
        CRUD::addColumn(['name' => 'title', 'label' => __('backend.register_input.title')]);
        CRUD::addColumn(['name' => 'name_form', 'label' => __('backend.register_input.name_form')]);
        CRUD::addColumn(['name' => 'type', 'label' => __('backend.register_input.type')]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(RegisterInputRequest::class);
        
        CRUD::addField([
            'name' => 'title',
            'label' => __('backend.register_input.title'),
            'type' => 'text'
        ]);
        CRUD::addField([
            'name' => 'name_form',
            'label' => __('backend.register_input.name_form'),
            'type' => 'text'
        ]);
        CRUD::addField([
            'name' => 'type',
            'label' => __('backend.register_input.type'),
            'type' => 'text'
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }


}
