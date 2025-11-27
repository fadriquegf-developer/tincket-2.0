<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tpv;
use App\Traits\AllowUsersTrait;
use App\Http\Requests\TpvRequest;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

class SettingsTpvCrudController extends CrudController
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

        CRUD::setModel(Tpv::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/custom-settings/tpv');
        CRUD::setEntityNameStrings(__('menu.setting_tpv'), __('menu.setting_tpv'));

    }

    protected function setupListOperation(): void
    {
        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.settings_tpv.name'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'omnipay_type',
            'label' => __('backend.settings_tpv.omnipay_type'),
            'type' => 'text',
        ]);

        // Nuevas columnas
        CRUD::addColumn([
            'name' => 'is_active',
            'label' => 'Activo',
            'type' => 'boolean',
        ]);

        CRUD::addColumn([
            'name' => 'is_test_mode',
            'label' => 'Modo Test',
            'type' => 'boolean',
        ]);

        CRUD::addColumn([
            'name' => 'is_default',
            'label' => 'Por defecto',
            'type' => 'boolean',
        ]);
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(TpvRequest::class);

        CRUD::addField([
            'name' => 'name',
            'label' => __('backend.settings_tpv.name'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'omnipay_type',
            'label' => __('backend.settings_tpv.omnipay_type'),
            'type' => 'select_from_array',
            'options' => Tpv::TPV_TYPES,
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'is_active',
            'label' => 'TPV Activo',
            'type' => 'checkbox',
            'default' => 1,
            'wrapper' => [
                'class' => 'form-group col-md-4',
            ],
        ]);

        CRUD::addField([
            'name' => 'is_test_mode',
            'label' => 'Modo Test',
            'type' => 'checkbox',
            'hint' => 'Marcar si es un TPV de pruebas',
            'wrapper' => [
                'class' => 'form-group col-md-4',
            ],
        ]);

        CRUD::addField([
            'name' => 'is_default',
            'label' => 'TPV por defecto',
            'type' => 'checkbox',
            'hint' => 'Se usará por defecto en nuevas sesiones',
            'wrapper' => [
                'class' => 'form-group col-md-4',
            ],
        ]);

        CRUD::addField([
            'name' => 'config',
            'label' => __('backend.settings_tpv.config'),
            'type' => 'repeatable',
            'fields' => [
                [
                    'name' => 'key',
                    'type' => 'text',
                    'label' => __('backend.settings_tpv.key'),
                    'wrapper' => [
                        'class' => 'form-group col-md-6',
                    ],
                ],
                [
                    'name' => 'value',
                    'type' => 'text',
                    'label' => __('backend.settings_tpv.value'),
                    'wrapper' => [
                        'class' => 'form-group col-md-6',
                    ],
                ],
            ],
            'new_item_label' => __('backend.settings_tpv.new_item_label'),
            'init_rows' => 1,
            'min_rows' => 0,

        ]);
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
