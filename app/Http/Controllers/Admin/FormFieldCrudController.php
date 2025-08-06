<?php

namespace App\Http\Controllers\Admin;

use App\Models\FormField;
use Illuminate\Support\Str;
use App\Traits\CrudPermissionTrait;
use App\Http\Requests\FormFieldRequest;
use Illuminate\Support\Facades\Request;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ReorderOperation;


class FormFieldCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;
    use ReorderOperation;
    use CrudPermissionTrait;

    public function setup()
    {
        CRUD::setModel(FormField::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/form-field');
        CRUD::setEntityNameStrings(__('backend.menu.form_field'), __('backend.menu.form_fields'));

        CRUD::enableReorder('label', 1);
        $this->setAccessUsingPermissions();
        CRUD::allowAccess('reorder');

    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'label',
            'label' => __('backend.form_field.label'),
        ]);
    }

    protected function setupReorderOperation()
    {
        CRUD::set('reorder.label', 'label');
        CRUD::set('reorder.max_level', 1);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(FormFieldRequest::class);
        CRUD::addFields($this->getFields());

        // Añadir el script dinámico
        CRUD::addField([
            'name' => 'include_toggle_script',
            'type' => 'custom_html',
            'value' => '<script src="' . asset('assets/js/admin/forms/form-field-options-toggle.js') . '"></script>',
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function getFields(): array
    {
        return [
            [
                'name' => 'label',
                'label' => __('backend.form_field.label'),
                'type' => 'text',
                'wrapperAttributes' => ['class' => 'form-group col-md-4'],
            ],
            [
                'name' => 'name',
                'label' => __('backend.form_field.name'),
                'type' => 'text',
                'wrapperAttributes' => ['class' => 'form-group col-md-4'],
            ],
            [
                'name' => 'type',
                'label' => __('backend.form_field.type'),
                'type' => 'select_from_array',
                'options' => [
                    'text' => 'Text',
                    'date' => 'Date',
                    'textarea' => 'Textarea',
                    'select' => 'Select',
                    'radio' => 'RadioButton',
                    'checkbox' => 'CheckBox',
                ],
                'default' => 'text',
                'allows_null' => false,
                'attributes' => [
                    'id' => 'field_type',
                ],
                'wrapperAttributes' => ['class' => 'form-group col-md-4'],
            ],
            [
                'name' => 'weight',
                'type' => 'hidden',
                'default' => 1,
            ],
            [
                'name' => 'is_editable',
                'type' => 'hidden',
                'default' => 1,
            ],
            [
                'name' => 'options',
                'label' => 'Opciones',
                'type' => 'repeatable',
                'new_item_label' => __('backend.form_field.add_option'),
                'init_rows' => 0,
                'fields' => [
                    [
                        'name' => 'value',
                        'type' => 'text',
                        'label' => 'Valor',
                        'wrapperAttributes' => ['class' => 'mb-1'],
                    ],
                    [
                        'name' => 'label',
                        'type' => 'text',
                        'label' => 'Etiqueta (opcional)',
                        'wrapperAttributes' => ['class' => 'mb-1'],
                    ],
                ],
                'store_in' => 'config',
                'fake' => true,
                'wrapperAttributes' => [
                    'data-field-name' => 'options',
                    'class' => 'form-group col-md-12 compact-repeatable',
                ],
            ],
            [
                'name' => 'required',
                'label' => __('backend.form_field.required'),
                'type' => 'switch',
                'store_in' => 'config',
                'fake' => true,
            ],
        ];
    }
}