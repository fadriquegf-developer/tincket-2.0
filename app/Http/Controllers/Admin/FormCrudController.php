<?php

namespace App\Http\Controllers\Admin;

use App\Models\Form;
use App\Models\FormField;
use App\Traits\CrudPermissionTrait;
use App\Http\Requests\FormCrudRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Http\Controllers\Operations\FetchOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

class FormCrudController extends CrudController
{
    use ShowOperation;
    use ListOperation;
    use CreateOperation { store as traitStore;
    }
    use UpdateOperation { update as traitUpdate;
    }
    use DeleteOperation;
    use CrudPermissionTrait;

    public function setup()
    {
        CRUD::setModel(Form::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/form');
        CRUD::setEntityNameStrings(__('backend.menu.form'), __('backend.menu.forms'));

        $this->setAccessUsingPermissions();
    }

    protected function setupShowOperation()
    {
        $form = $this->crud->getCurrentEntry();

        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.form.name'),
            'type' => 'text',
        ]);

        $html = '<div class="container-fluid"><div class="row">';

        $count = 0;

        foreach ($form->form_fields()->orderBy('weight')->get() as $field) {
            $label = $field->label;
            $type = $field->type;
            $config = is_string($field->config) ? json_decode($field->config, true) : json_decode(json_encode($field->config), true);

            // Abrir una nueva columna
            $html .= '<div class="col-md-6" style="margin-bottom: 1rem;">';
            $html .= '<label style="display: block; font-weight: 600; margin-bottom: .5rem;">' . e($label) . '</label>';

            switch ($type) {
                case 'text':
                case 'date':
                    $html .= '<input type="' . e($type) . '" class="form-control"/>';
                    break;

                case 'textarea':
                    $html .= '<textarea class="form-control" rows="2"></textarea>';
                    break;

                case 'checkbox':
                    foreach ($config['options'] ?? [] as $opt) {
                        $labelOpt = is_array($opt['label']) ? ($opt['label'][app()->getLocale()] ?? reset($opt['label'])) : $opt['label'];
                        $html .= '<div class="form-check">';
                        $html .= '<input class="form-check-input" type="checkbox">';
                        $html .= '<label class="form-check-label" style="margin-left: .5rem;">' . e($labelOpt) . '</label>';
                        $html .= '</div>';
                    }
                    break;

                case 'radio':
                    $radioGroupName = 'preview_radio_' . $field->id;
                    foreach ($config['options'] ?? [] as $opt) {
                        $optLabel = is_array($opt['label']) ? ($opt['label'][app()->getLocale()] ?? reset($opt['label'])) : $opt['label'];
                        $html .= '<div class="form-check">';
                        $html .= '<input class="form-check-input" type="radio" name="' . e($radioGroupName) . '>';
                        $html .= '<label class="form-check-label" style="margin-left: .5rem;">' . e($optLabel) . '</label>';
                        $html .= '</div>';
                    }
                    break;

                case 'select':
                    $options = $config['options'] ?? [];
                    $html .= '<select class="form-control">';
                    foreach ($options as $opt) {
                        $label = $opt['label'] ?? '';
                        if (is_array($label)) {
                            $label = $label[app()->getLocale()] ?? reset($label);
                        }
                        $html .= '<option>' . e($label) . '</option>';
                    }
                    $html .= '</select>';
                    break;

                default:
                    $html .= '<input type="text" class="form-control" disabled />';
                    break;
            }

            $html .= '</div>';
            $count++;
        }

        $html .= '</div></div>';

        $this->crud->addColumn([
            'name' => 'form_preview',
            'type' => 'custom_html',
            'escaped' => false,
            'value' => new \Illuminate\Support\HtmlString($html),
        ]);
    }






    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.form.name'),
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(FormCrudRequest::class);

        CRUD::addField([
            'name' => 'name',
            'label' => __('backend.form.name'),
            'type' => 'text',
            'hint' => '<a href="/form-field/create" class="btn btn-sm btn-primary mt-4">Nuevo campo</a>',
        ]);

        CRUD::addField([
            'name' => 'form_fields',
            'label' => __('backend.form.form_fields'),
            'type' => 'select_and_order',
            'entity' => 'form_fields',
            'attribute' => 'label',
            'model' => FormField::class,
            'pivot' => true,
            'options' => FormField::all()->mapWithKeys(function ($item) {
                return [$item->id => $item->label];
            }),
        ]);

    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();

        $entry = $this->crud->getCurrentEntry();

        $this->crud->modifyField('form_fields', [
            'value' => $entry->form_fields()
                ->orderBy('form_form_field.order')
                ->pluck('form_field_id')
                ->toArray()
        ]);
    }

    public function store(FormCrudRequest $request)
    {
        $response = $this->traitStore();

        $fields = collect($request->input('form_fields', []))
            ->map(fn($item) => is_array($item) && isset($item['id']) ? (int) $item['id'] : (int) $item)
            ->toArray();

        $this->saveFormFieldsWithOrder($this->crud->entry, $fields);

        return $response;
    }

    public function update(FormCrudRequest $request)
    {
        $response = $this->traitUpdate();

        $fields = collect($request->input('form_fields', []))
            ->map(fn($item) => is_array($item) && isset($item['id']) ? (int) $item['id'] : (int) $item)
            ->toArray();

        $this->saveFormFieldsWithOrder($this->crud->entry, $fields);

        return $response;
    }

    protected function saveFormFieldsWithOrder(Form $form, array $fields)
    {
        $syncData = [];

        foreach ($fields as $index => $fieldId) {
            $syncData[$fieldId] = ['order' => $index];
        }

        $form->form_fields()->sync($syncData);
    }
}
