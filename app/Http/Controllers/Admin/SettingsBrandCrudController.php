<?php

namespace App\Http\Controllers\Admin;

use App\Models\Brand;
use App\Models\Taxonomy;
use App\Traits\AllowUsersTrait;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class SettingsBrandCrudController extends CrudController
{
    use AllowUsersTrait;

    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;


    public function setup()
    {
        /* Solamente superusuarios tienen acceso */
        $this->isSuperuser();

        CRUD::setModel(Brand::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/custom-settings/brand');
        CRUD::setEntityNameStrings(__('backend.menu.brand_settings'), __('backend.menu.brands_settings'));
    }

    /**
     * Sobreescribimos el index() para redirigir directamente al edit de la Brand actual.
     */
    public function index()
    {
        $brand = get_current_brand();
        if ($brand) {
            $editUrl = backpack_url("custom-settings/brand/{$brand->id}/edit");
            return redirect($editUrl);
        }
        abort(404, 'Brand no encontrada');
    }

    /**
     * Configuramos la operación de actualización con los campos adicionales.
     */
    protected function setupUpdateOperation()
    {

        $this->setupBrandTab();
        $this->setupAlertTab();
        $this->setupTaxonomiesTab();
        $this->setupTpvTab();
        $this->setupCartTab();
        //$this->setupRegisterTab();
        $this->setupLegalTab();
    }

    private function setupBrandTab()
    {

        $brand = get_current_brand();

        CRUD::field('logo')
            ->type('image')
            ->label(__('backend.brand_settings.logo'))
            ->crop(true)->aspect_ratio(2.5 / 1)
            ->withFiles([
                'disk'      => 'public',
                'path'      => "uploads/{$brand->code_name}/media",
                'uploader'  => \App\Uploaders\WebpImageUploader::class,
                'fileNamer' => fn($file, $u) => 'logo-' . $u->entry->code_name . '.webp',
                'resize'      => ['max' => 300],
            ])
            ->wrapper(['class' => 'form-group col-md-6'])
            ->tab(__('backend.brand_settings.tabs.brand'));

        CRUD::field('banner')
            ->type('image')
            ->label(__('backend.brand_settings.banner'))
            ->crop(true)
            ->aspect_ratio(2.5 / 1)
            ->withFiles([
                'disk'       => 'public',
                'path'       => "uploads/{$brand->code_name}/media",
                'uploader'   => \App\Uploaders\WebpImageUploader::class,
                'fileNamer'  => fn($file, $u) => 'banner-' . $u->entry->code_name . '.webp',
                'resize'      => ['max' => 1200],
                'conversions' => [],
            ])
            ->wrapper(['class' => 'form-group col-md-6'])
            ->tab(__('backend.brand_settings.tabs.brand'));


        CRUD::field('brand_color')
            ->label(__('backend.brand_settings.color'))
            ->type('color')
            ->tab(__('backend.brand_settings.tabs.brand'))
            ->wrapper(['class' => 'form-group col-md-3']);

        CRUD::field('description')
            ->label(__('backend.brand_settings.description'))
            ->type('ckeditor')
            ->tab(__('backend.brand_settings.tabs.brand'));

        CRUD::field('footer')
            ->label(__('backend.brand_settings.footer'))
            ->type('ckeditor')
            ->tab(__('backend.brand_settings.tabs.brand'));

        CRUD::field('custom_script')
            ->label(__('backend.brand_settings.custom_script'))
            ->type('textarea')
            ->tab(__('backend.brand_settings.tabs.brand'));

        CRUD::field('aux_code')
            ->label(__('backend.brand_settings.aux_code'))
            ->type('text')
            ->tab(__('backend.brand_settings.tabs.brand'));

        CRUD::field('google_recaptcha_secret_key')
            ->label(__('backend.brand_settings.google_recaptcha_secret_key'))
            ->type('text')
            ->fake(true)
            ->store_in('extra_config')
            ->tab(__('backend.brand_settings.tabs.brand'));

        CRUD::field('google_recaptcha_site_key')
            ->label(__('backend.brand_settings.google_recaptcha_site_key'))
            ->type('text')
            ->fake(true)
            ->store_in('extra_config')
            ->tab(__('backend.brand_settings.tabs.brand'));

        CRUD::field('link_politica_privacidad')
            ->label(__('backend.brand_settings.link_politica_privacidad'))
            ->type('text')
            ->fake(true)
            ->store_in('extra_config')
            ->tab(__('backend.brand_settings.tabs.brand'));

        CRUD::field('barra_de_cerca_filtre_comarca')
            ->label(__('backend.brand_settings.barra_de_cerca_filtre_comarca'))
            ->type('switch')
            ->fake(true)
            ->store_in('extra_config')
            ->tab(__('backend.brand_settings.tabs.brand'));

        CRUD::field('mantenimiento')
            ->label(__('backend.brand_settings.mantenimiento'))
            ->type('switch')
            ->fake(true)
            ->store_in('extra_config')
            ->tab(__('backend.brand_settings.tabs.brand'));
    }

    private function setupAlertTab()
    {
        CRUD::field('alert_status')
            ->label(__('backend.brand_settings.alert_status'))
            ->type('switch')
            ->tab(__('backend.brand_settings.tabs.alert'));

        CRUD::field('alert')
            ->label(__('backend.brand_settings.alert'))
            ->type('ckeditor')
            ->tab(__('backend.brand_settings.tabs.alert'));
    }

    private function setupTaxonomiesTab()
    {
        CRUD::addField([
            // select_from_array
            'name' => 'main_taxonomy_id',
            'label' => __('backend.brand_settings.maintaxonomy'),
            'type' => 'select2_from_builder',
            'builder' => Taxonomy::query()->orderBy('depth', 'asc')->orderBy('rgt', 'asc'),
            'key' => 'id',
            'attribute' => 'name',
            'fake' => true,
            'store_in' => 'extra_config',
            'tab' => __('backend.brand_settings.tabs.taxonomies'),
            'hint' => '<span class="small">'.__('backend.brand_settings.taxonomychildswillbedisplayed').'</span>'
        ]);
        
        CRUD::addField([
            'label'     => __('backend.brand_settings.hiddentaxonomies'),
            'type'      => 'checklist_hidden_taxonomies',
            'name'      => 'hidden_taxonomies',
            'model'     => Taxonomy::class, // <- Asegúrate que sea string o use completo
            'attribute' => 'name',
            'fake'      => true,
            'store_in'  => 'extra_config',
            'tab'       => __('backend.brand_settings.tabs.taxonomies'),
            'hint'      => '<span class="small">'
                . __('backend.brand_settings.select_which_taxonomies_will')
                . ' <strong>' . __('backend.brand_settings.not') . '</strong>'
                . __('backend.brand_settings.be_shown_in_fronend') . '.</span>',
        ]);

        CRUD::addField([
            // select_from_array
            'name' => 'posting_taxonomy_id',
            'label' => __('backend.brand_settings.postingtaxonomy'),
            'type' => 'select2_from_builder',
            'builder' => Taxonomy::query()->orderBy('depth', 'asc')->orderBy('rgt', 'asc'),
            'key' => 'id',
            'attribute' => 'name',
            'fake' => true,
            'store_in' => 'extra_config',
            'tab' => __('backend.brand_settings.tabs.taxonomies'),
            'hint' => '<span class="small">'.__('backend.brand_settings.this_should_be_the_posts_taxonomy').'</span>'
        ]);

        CRUD::addField([
            'name' => 'seasons_taxonomy_id',
            'label' => __('backend.brand_settings.seasonstaxonomy'),
            'type' => 'select2_from_builder',
            'builder' => Taxonomy::query()->orderBy('depth', 'asc')->orderBy('rgt', 'asc'),
            'key' => 'id',
            'attribute' => 'name',
            'fake' => true,
            'allows_null' => true,
            'store_in' => 'extra_config',
            'tab' => __('backend.brand_settings.tabs.taxonomies'),
        ]); 
    }

    private function setupTpvTab()
    {
        CRUD::addField([
            'name' => 'default_tpv_id',
            'label' => __('backend.brand_settings.maintpv'),
            'type' => 'select2_from_array',
            'options' => get_current_brand()
                ->tpvs()
                ->pluck('name', 'id')
                ->toArray(),
            'fake' => true,
            'store_in' => 'extra_config',
            'tab' => 'TPV',
            'hint' => '<span class="small">' . __('backend.brand_settings.default_tpv') . '</span>',
        ]);
    }

    private function setupCartTab()
    {
        CRUD::field('cartTTL')
            ->label(__('backend.brand_settings.cartTTL'))
            ->type('number')
            ->fake(true)
            ->store_in('extra_config')
            ->wrapper(['class' => 'form-group col-md-6'])
            ->tab(__('backend.brand_settings.tabs.cart'));

        CRUD::field('clearfix')
            ->type('custom_html')
            ->value('<div class="clearfix"></div>')
            ->tab(__('backend.brand_settings.tabs.cart'));

        CRUD::field('maxCartTTL')
            ->label(__('backend.brand_settings.maxCartTTL'))
            ->type('number')
            ->fake(true)
            ->store_in('extra_config')
            ->wrapper(['class' => 'form-group col-md-6'])
            ->tab(__('backend.brand_settings.tabs.cart'));
    }

    /* private function setupRegisterTab()
    {
        $register_inputs = RegisterInput::get();

        foreach($register_inputs as $input){
            $this->crud->addField([
                'name' => "input[".$input->name_form."][id]", // JSON variable name
                'label' => $input->title, // human-readable label for the input
                'tab' => 'Registre',
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-12',
                ],
                'value' => $input->id,
                'attributes' => [
                    'readonly' => 'readonly',
                    'style' => 'display:none;',
                  ],
            ]);
            $this->crud->addField([   // Checkbox
                'name' => "input[".$input->name_form."][active]",
                'label' => 'Active',
                'type' => 'checkbox',
                'value' => request()->get('brand')->register_inputs()->where('register_inputs.id', $input->id)->first() ? true : false,
                'tab' => 'Registre',
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-4',
                ],
            ]);
            $this->crud->addField([   // Checkbox
                'name' => "input[".$input->name_form."][required]",
                'label' => 'Required',
                'type' => 'checkbox',
                'value' => request()->get('brand')->register_inputs()->where('register_inputs.id', $input->id)->first() ? request()->get('brand')->register_inputs()->where('register_inputs.id', $input->id)->first()->pivot->required : false,
                'tab' => 'Registre',
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-4',
                ],
            ]);
        }
        
    } */

    private function setupLegalTab()
    {
        CRUD::field('legal_notice')
            ->label(__('backend.brand_settings.legal_notice'))
            ->type('ckeditor')
            ->extraPlugins(['oembed'])
            ->tab(__('backend.brand_settings.tabs.legal'));

        CRUD::field('privacy_policy')
            ->label(__('backend.brand_settings.privacy_policy'))
            ->type('ckeditor')
            ->extraPlugins(['oembed'])
            ->tab(__('backend.brand_settings.tabs.legal'));

        CRUD::field('cookies_policy')
            ->label(__('backend.brand_settings.cookies_policy'))
            ->type('ckeditor')
            ->extraPlugins(['oembed'])
            ->tab(__('backend.brand_settings.tabs.legal'));

        CRUD::field('general_conditions')
            ->label(__('backend.brand_settings.general_conditions'))
            ->type('ckeditor')
            ->extraPlugins(['oembed'])
            ->tab(__('backend.brand_settings.tabs.legal'));

        CRUD::field('gdpr_text')
            ->label(__('backend.brand_settings.gdpr_text'))
            ->type('ckeditor')
            ->extraPlugins(['oembed'])
            ->tab(__('backend.brand_settings.tabs.legal'));

        CRUD::field('clausule_general_status')
            ->label(__('backend.brand_settings.clausule_general_status'))
            ->type('switch')
            ->wrapperAttributes(['class' => 'col-lg-6 form-group form-inline'])
            ->fake(true)
            ->store_in('extra_config')
            ->tab(__('backend.brand_settings.tabs.legal'));

        CRUD::field('responsable_tratamiento')
            ->label(__('backend.brand_settings.responsable_tratamiento'))
            ->type('text')
            ->fake(true)
            ->store_in('extra_config')
            ->tab(__('backend.brand_settings.tabs.legal'));

        CRUD::field('delegado_proteccion')
            ->label(__('backend.brand_settings.delegado_proteccion'))
            ->type('text')
            ->fake(true)
            ->store_in('extra_config')
            ->tab(__('backend.brand_settings.tabs.legal'));
    }
}
