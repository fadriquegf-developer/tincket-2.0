<?php

namespace App\Http\Controllers\Admin;

use Str;
use App\Traits\TMFTemplatesTrait;
use App\Traits\PageTemplatesTrait;
use App\Traits\CrudPermissionTrait;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\PageManager\app\Http\Requests\PageRequest;

class PageCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        create as traitCreate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        edit as traitEdit;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use PageTemplatesTrait, TMFTemplatesTrait;
    use CrudPermissionTrait;

    public function setup()
    {
        $this->crud->setModel(config('backpack.pagemanager.page_model_class', 'Backpack\PageManager\app\Models\Page'));
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/page');
        $this->crud->setEntityNameStrings(__('backend.menu.page'), __('backend.menu.pages'));
        $this->setAccessUsingPermissions();
    }

    protected function setupListOperation()
    {
        $this->crud->addColumn([
            'name' => 'name',
            'label' => trans('backpack::pagemanager.name'),
        ]);
        $this->crud->addColumn([
            'name' => 'template',
            'label' => trans('backpack::pagemanager.template'),
            'type' => 'model_function',
            'function_name' => 'getTemplateName',
        ]);
        $this->crud->addColumn([
            'name' => 'slug',
            'label' => trans('backpack::pagemanager.slug'),
        ]);
        $this->crud->addButtonFromModelFunction('line', 'open', 'getOpenButton', 'beginning');
    }

    // -----------------------------------------------
    // Overwrites of CrudController
    // -----------------------------------------------

    protected function setupCreateOperation()
    {
        // Note:
        // - default fields, that all templates are using, are set using $this->addDefaultPageFields();
        // - template-specific fields are set per-template, in the PageTemplates trait;

        $this->addDefaultPageFields(\Request::input('template'));
        $this->useTemplate(\Request::input('template'));

        $this->crud->setValidation(PageRequest::class);
    }

    protected function setupUpdateOperation()
    {
        // if the template in the GET parameter is missing, figure it out from the db
        $template = \Request::input('template') ?? $this->crud->getCurrentEntry()->template;

        $this->addDefaultPageFields($template);
        $this->useTemplate($template);

        $this->crud->setValidation(PageRequest::class);
    }

    // -----------------------------------------------
    // Methods that are particular to the PageManager.
    // -----------------------------------------------

    /**
     * Populate the create/update forms with basic fields, that all pages need.
     *
     * @param  string  $template  The name of the template that should be used in the current form.
     */
    public function addDefaultPageFields($template = false)
    {
        $this->crud->addField([
            'name' => 'template',
            'label' => __('backend.page.template'),
            'type' => 'select_page_template',
            'view_namespace' => file_exists(resource_path('views/vendor/backpack/crud/fields/select_page_template.blade.php')) ? null : 'pagemanager::fields',
            'options' => $this->getTemplatesArray(),
            'value' => $template,
            'allows_null' => false,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => __('backend.page.content')
        ]);
        $this->crud->addField([
            'name' => 'name',
            'label' => __('backend.page.page_name'),
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => __('backend.page.content')
        ]);
        $this->crud->addField([
            'name' => 'title',
            'label' => __('backend.page.page_title'),
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => __('backend.page.content')
        ]);
        $this->crud->addField([
            'name' => 'slug',
            'label' => __('backend.page.page_slug'),
            'type' => 'slug',
            'target' => 'title',
            'hint' => __('backend.page.page_slug_hint'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => __('backend.page.content')
        ]);
        $this->crud->addField([
            'name' => 'meta_title',
            'label' => 'Meta ' . __('backend.page.page_title'),
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => 'Meta'
        ]);
        $this->crud->addField([
            'name' => 'meta_description',
            'label' => 'Meta ' . __('backend.page.page_title'),
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => 'Meta'
        ]);
    }

    /**
     * Add the fields defined for a specific template.
     *
     * @param  string  $template_name  The name of the template that should be used in the current form.
     */
    public function useTemplate($template_name = false)
    {
        $templates = $this->getTemplates();

        // set the default template
        if ($template_name == false) {
            $template_name = $templates[0]->name;
        }

        // actually use the template
        if ($template_name) {
            $this->{$template_name}();
        }
    }

    /**
     * Get all defined templates.
     */
    public function getTemplates($template_name = false)
    {
        $templates_array = [];

        /* Si es Torello Mountain Film */
        if (get_current_brand()->id == 1) {
            $templates_trait = new \ReflectionClass('App\Traits\TMFTemplatesTrait');
        } else {
            $templates_trait = new \ReflectionClass('App\Traits\PageTemplatesTrait');
        }

        $templates = $templates_trait->getMethods(\ReflectionMethod::IS_PRIVATE);

        if (!count($templates)) {
            abort(503, trans('backpack::pagemanager.template_not_found'));
        }

        return $templates;
    }

    /**
     * Get all defined template as an array.
     *
     * Used to populate the template dropdown in the create/update forms.
     */
    public function getTemplatesArray()
    {
        $templates = $this->getTemplates();

        foreach ($templates as $template) {
            $templates_array[$template->name] = str_replace('_', ' ', Str::title($template->name));
        }

        return $templates_array;
    }
}
