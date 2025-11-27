<?php

namespace App\Http\Controllers\Admin;

use App\Models\FormField;
use Prologue\Alerts\Facades\Alert;
use App\Traits\CrudPermissionTrait;
use App\Http\Requests\FormFieldRequest;
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
    use CreateOperation {
        store as traitStore;
    }
    use UpdateOperation {
        update as traitUpdate;
    }
    use DeleteOperation;
    use ReorderOperation;
    use CrudPermissionTrait;

    public function setup()
    {
        CRUD::setModel(FormField::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/form-field');
        CRUD::setEntityNameStrings(__('menu.form_field'), __('menu.form_fields'));

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

        CRUD::addColumn([
            'name' => 'type',
            'label' => __('backend.form_field.type'),
            'type' => 'text',
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

    /**
     * Sobrescribir store para procesar config manualmente
     */
    public function store()
    {
        $this->crud->setRequest($this->crud->validateRequest());
        $this->processConfigFields();

        $item = $this->crud->create($this->crud->getRequest()->except(['_token', '_method', '_http_referrer']));

        Alert::success(trans('backpack::crud.insert_success'))->flash();
        return $this->crud->performSaveAction($item->getKey());
    }

    /**
     * Sobrescribir update para procesar config manualmente
     */
    public function update()
    {
        $this->crud->setRequest($this->crud->validateRequest());
        $this->processConfigFields();

        $item = $this->crud->update(
            $this->crud->getCurrentEntryId(),
            $this->crud->getRequest()->except(['_token', '_method', '_http_referrer'])
        );

        Alert::success(trans('backpack::crud.update_success'))->flash();
        return $this->crud->performSaveAction($item->getKey());
    }

    /**
     * Procesar campos que van en config
     */
    protected function processConfigFields()
    {
        $request = $this->crud->getRequest();
        $data = $request->all();

        // Obtener config existente si es update
        $existingConfig = [];
        if ($request->route('id')) {
            $entry = FormField::find($request->route('id'));
            if ($entry) {
                $existingConfig = $entry->config ?? [];
            }
        }

        $config = $existingConfig;
        $currentLocale = app()->getLocale();

        // Procesar field_options
        if (isset($data['field_options'])) {
            $oldOptions = $config['options'] ?? [];
            $oldOptionsByValue = collect($oldOptions)->keyBy('value');

            $options = collect($data['field_options'])->map(function ($option) use ($currentLocale, $oldOptionsByValue) {
                $value = $option['value'] ?? '';
                $newLabel = $option['label'] ?? '';

                // Generar key
                $optionValue = FormField::stripAccents($value);
                $optionValue = preg_replace('/[^\w\s]/u', '', $optionValue);
                $key = str_replace(" ", "", ucwords($optionValue));

                // Preservar traducciones
                $oldOption = $oldOptionsByValue->get($value);

                if ($oldOption && isset($oldOption['label']) && is_array($oldOption['label'])) {
                    // Preservar traducciones existentes
                    $label = $oldOption['label'];
                    $label[$currentLocale] = $newLabel;
                } else {
                    // Nueva opción
                    $label = [$currentLocale => $newLabel];
                }

                return [
                    'value' => $value,
                    'key' => $key,
                    'label' => $label,
                ];
            })->filter(function ($option) {
                return !empty($option['value']);
            })->values()->toArray();

            $config['options'] = $options;
            unset($data['field_options']);
        }

        // Procesar field_required
        if (isset($data['field_required'])) {
            $config['required'] = filter_var($data['field_required'], FILTER_VALIDATE_BOOLEAN);
            unset($data['field_required']);
        }

        // Guardar config procesado
        $data['config'] = $config;

        // Actualizar request
        $request->replace($data);
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
                'name' => 'field_options',
                'label' => 'Opciones',
                'type' => 'repeatable',
                'new_item_label' => __('backend.form_field.add_option'),
                'init_rows' => 1,
                'min_rows' => 0,
                'max_rows' => 50,
                'fields' => [
                    [
                        'name' => 'value',
                        'type' => 'text',
                        'label' => 'Valor',
                        'wrapper' => ['class' => 'form-group col-md-6'],
                    ],
                    [
                        'name' => 'label',
                        'type' => 'text',
                        'label' => 'Etiqueta',
                        'wrapper' => ['class' => 'form-group col-md-6'],
                    ],
                ],
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-12',
                ],
            ],
            [
                'name' => 'field_required',
                'label' => __('backend.form_field.required'),
                'type' => 'switch',
            ],
        ];
    }
}