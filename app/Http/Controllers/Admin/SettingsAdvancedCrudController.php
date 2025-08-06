<?php

namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use App\Traits\AllowUsersTrait;
use App\Http\Requests\SettingRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

class SettingsAdvancedCrudController extends CrudController
{
    use AllowUsersTrait;
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup(): void
    {
        /* Solamente superusuarios tienen acceso */
        $this->isSuperuser();

        CRUD::setModel(Setting::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/custom-settings/advanced');
        CRUD::setEntityNameStrings(__('backend.menu.setting_advanced'), __('backend.menu.settings_advanced'));

    }

    protected function setupListOperation(): void
    {
        CRUD::setDefaultPageLength(50);
        CRUD::addColumn([
            'name' => 'key',
            'label' => __('backend.settings_advanced.key'),
            'type' => 'text',
            'limit' => true,
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addColumn([
            'name' => 'value',
            'label' => __('backend.settings_advanced.value'),
            'type' => 'text',
            'limit' => true,
        ]);
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(SettingRequest::class);
        CRUD::addField([
            'name' => 'key',
            'label' => __('backend.settings_advanced.key'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'value',
            'label' => __('backend.settings_advanced.value'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
