<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UpdateBrandSettingsRequest;
use App\Models\Brand;
use App\Models\Taxonomy;
use App\Models\RegisterInput;
use App\Traits\AllowUsersTrait;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prologue\Alerts\Facades\Alert;

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
        if (!$this->isSuperuser()) {
            abort(403, __('backend.brand_settings.errors.access_denied'));
        }

        // Verificar que existe una brand
        $brand = get_current_brand();
        if (!$brand) {
            abort(404, __('backend.brand_settings.errors.brand_not_found'));
        }

        CRUD::setModel(Brand::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/custom-settings/brand');
        CRUD::setEntityNameStrings(__('menu.configuration_general'), __('menu.configuration_general'));
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
        abort(404, __('backend.brand_settings.errors.brand_not_found'));
    }

    /**
     * Configuramos la operación de actualización con los campos adicionales.
     */
    protected function setupUpdateOperation()
    {
        CRUD::setValidation(UpdateBrandSettingsRequest::class);

        $this->setupBrandTab();
        $this->setupAlertTab();
        $this->setupCategoriesTab();
        $this->setupSeasonsTab();
        $this->setupTpvTab();
        $this->setupCartTab();
        $this->setupRegisterTab();
        $this->setupLegalTab();
    }

    private function setupBrandTab()
    {
        $brand = get_current_brand();

        CRUD::field('logo')
            ->type('image')
            ->label(__('backend.brand_settings.logo'))
            ->crop(true)
            ->withFiles([
                'disk' => 'public',
                'path' => "uploads/{$brand->code_name}/media",
                'uploader' => \App\Uploaders\WebpImageUploader::class,
                'fileNamer' => fn($file, $u) => 'logo-' . $u->entry->code_name . '.webp',
                'resize' => ['max' => 300],
            ])
            ->wrapper(['class' => 'form-group col-md-6'])
            ->tab(__('backend.brand_settings.tabs.brand'));

        CRUD::field('banner')
            ->type('image')
            ->label(__('backend.brand_settings.banner'))
            ->crop(true)
            ->aspect_ratio(2.5 / 1)
            ->withFiles([
                'disk' => 'public',
                'path' => "uploads/{$brand->code_name}/media",
                'uploader' => \App\Uploaders\WebpImageUploader::class,
                'fileNamer' => fn($file, $u) => 'banner-' . $u->entry->code_name . '.webp',
                'resize' => ['max' => 1200],
                'conversions' => [],
            ])
            ->wrapper(['class' => 'form-group col-md-6'])
            ->tab(__('backend.brand_settings.tabs.brand'))
            ->hint(__('backend.brand_settings.banner_hint'));

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

        // Campos peligrosos, solo a superusuarios
        if ($this->isSuperuser()) {
            CRUD::field('custom_script')
                ->label(__('backend.brand_settings.custom_script'))
                ->type('textarea')
                ->tab(__('backend.brand_settings.tabs.brand'))
                ->attributes([
                    'rows' => 10,
                    'placeholder' => __('backend.brand_settings.custom_script_placeholder')
                ])
                ->hint(__('backend.brand_settings.custom_script_warning'));

            CRUD::field('aux_code')
                ->label(__('backend.brand_settings.aux_code'))
                ->type('textarea')
                ->tab(__('backend.brand_settings.tabs.brand'))
                ->attributes([
                    'rows' => 5
                ])
                ->hint(__('backend.brand_settings.aux_code_warning'));
        }

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
            ->tab(__('backend.brand_settings.tabs.brand'))
            ->hint(__('backend.brand_settings.mantenimiento_warning'));
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

    private function setupCategoriesTab()
    {
        $brandId = get_current_brand_id();

        // Taxonomía principal para categorizar eventos - FILTRADA POR BRAND
        CRUD::addField([
            'name' => 'main_taxonomy_id',
            'label' => __('backend.brand_settings.main_category'),
            'type' => 'select2_from_builder',
            'builder' => Taxonomy::query()
                ->where('brand_id', $brandId)
                ->orderBy('depth', 'asc')
                ->orderBy('rgt', 'asc'),
            'key' => 'id',
            'attribute' => 'name',
            'fake' => true,
            'store_in' => 'extra_config',
            'tab' => __('backend.brand_settings.tabs.categories'),
            'hint' => '<span class="small text-muted">' . __('backend.brand_settings.main_category_hint') . '</span>'
        ]);

        // Taxonomía para posts/noticias - FILTRADA POR BRAND
        CRUD::addField([
            'name' => 'posting_taxonomy_id',
            'label' => __('backend.brand_settings.news_category'),
            'type' => 'select2_from_builder',
            'builder' => Taxonomy::query()
                ->where('brand_id', $brandId)
                ->orderBy('depth', 'asc')
                ->orderBy('rgt', 'asc'),
            'key' => 'id',
            'attribute' => 'name',
            'fake' => true,
            'store_in' => 'extra_config',
            'tab' => __('backend.brand_settings.tabs.categories'),
            'hint' => '<span class="small text-muted">' . __('backend.brand_settings.news_category_hint') . '</span>'
        ]);

        // Categorías ocultas - AHORA CON BRAND_ID
        CRUD::addField([
            'label' => __('backend.brand_settings.hidden_categories'),
            'type' => 'checklist_hidden_taxonomies',
            'name' => 'hidden_taxonomies',
            'model' => Taxonomy::class,
            'attribute' => 'name',
            'fake' => true,
            'store_in' => 'extra_config',
            'tab' => __('backend.brand_settings.tabs.categories'),
            'hint' => '<span class="small text-muted">' . __('backend.brand_settings.hidden_categories_hint') . '</span>',
            'brand_id' => $brandId,  // Pasar el brand_id al campo
        ]);
    }

    private function setupSeasonsTab()
    {
        $brandId = get_current_brand_id();

        // Taxonomía de temporadas - FILTRADA POR BRAND
        CRUD::addField([
            'name' => 'seasons_taxonomy_id',
            'label' => __('backend.brand_settings.seasons_category'),
            'type' => 'select2_from_builder',
            'builder' => Taxonomy::query()
                ->where('brand_id', $brandId)
                ->orderBy('depth', 'asc')
                ->orderBy('rgt', 'asc'),
            'key' => 'id',
            'attribute' => 'name',
            'fake' => true,
            'allows_null' => true,
            'store_in' => 'extra_config',
            'tab' => __('backend.brand_settings.tabs.seasons'),
            'hint' => '<span class="small text-muted">' . __('backend.brand_settings.seasons_category_hint') . '</span>'
        ]);

        // Opción para mostrar/ocultar agrupación por temporadas
        CRUD::addField([
            'name' => 'enable_seasons_grouping',
            'label' => __('backend.brand_settings.enable_seasons_grouping'),
            'type' => 'switch',
            'fake' => true,
            'store_in' => 'extra_config',
            'tab' => __('backend.brand_settings.tabs.seasons'),
            'hint' => '<span class="small text-muted">' . __('backend.brand_settings.enable_seasons_grouping_hint') . '</span>'
        ]);
    }

    private function setupTpvTab()
    {
        $brand = get_current_brand();
        $tpvOptions = $brand->tpvs()->pluck('name', 'id')->toArray();

        if (empty($tpvOptions)) {
            CRUD::addField([
                'name' => 'no_tpvs_message',
                'type' => 'custom_html',
                'value' => '<div class="alert alert-warning">' . __('backend.brand_settings.no_tpvs_available') . '</div>',
                'tab' => 'TPV',
            ]);
        } else {
            CRUD::addField([
                'name' => 'default_tpv_id',
                'label' => __('backend.brand_settings.maintpv'),
                'type' => 'select2_from_array',
                'options' => $tpvOptions,
                'fake' => true,
                'store_in' => 'extra_config',
                'tab' => 'TPV',
                'hint' => '<span class="small">' . __('backend.brand_settings.default_tpv') . '</span>',
            ]);
        }
    }

    private function setupCartTab()
    {
        CRUD::field('cartTTL')
            ->label(__('backend.brand_settings.cartTTL'))
            ->type('number')
            ->fake(true)
            ->store_in('extra_config')
            ->wrapper(['class' => 'form-group col-md-6'])
            ->tab(__('backend.brand_settings.tabs.cart'))
            ->attributes(['min' => 1, 'max' => 1440])
            ->hint(__('backend.brand_settings.cartTTL_hint'));

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
            ->tab(__('backend.brand_settings.tabs.cart'))
            ->attributes(['min' => 1, 'max' => 2880])
            ->hint(__('backend.brand_settings.maxCartTTL_hint'));
    }

    private function setupRegisterTab()
    {
        $register_inputs = RegisterInput::all();

        // Precargar los inputs de la brand actual para evitar N+1
        $brandInputs = get_current_brand()
            ->register_inputs()
            ->get()
            ->keyBy('id');

        foreach ($register_inputs as $input) {
            $brandInput = $brandInputs->get($input->id);

            $this->crud->addField([
                'name' => "input.{$input->name_form}.id",
                'label' => $input->title,
                'tab' => __('backend.brand_settings.tabs.register'),
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-12',
                ],
                'value' => $input->id,
                'attributes' => [
                    'readonly' => 'readonly',
                    'style' => 'display:none;',
                ],
            ]);

            $this->crud->addField([
                'name' => "input.{$input->name_form}.active",
                'label' => __('backend.brand_settings.active'),
                'type' => 'checkbox',
                'value' => $brandInput ? true : false,
                'tab' => __('backend.brand_settings.tabs.register'),
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-4',
                ],
            ]);

            $this->crud->addField([
                'name' => "input.{$input->name_form}.required",
                'label' => __('backend.brand_settings.required'),
                'type' => 'checkbox',
                'value' => $brandInput ? $brandInput->pivot->required : false,
                'tab' => __('backend.brand_settings.tabs.register'),
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-4',
                ],
            ]);
        }
    }

    private function setupLegalTab()
    {
        CRUD::field('legal_notice')
            ->label(__('backend.brand_settings.legal_notice'))
            ->type('ckeditor')
            //->extraPlugins(['oembed'])
            ->tab(__('backend.brand_settings.tabs.legal'));

        CRUD::field('privacy_policy')
            ->label(__('backend.brand_settings.privacy_policy'))
            ->type('ckeditor')
            //->extraPlugins(['oembed'])
            ->tab(__('backend.brand_settings.tabs.legal'));

        CRUD::field('cookies_policy')
            ->label(__('backend.brand_settings.cookies_policy'))
            ->type('ckeditor')
            //->extraPlugins(['oembed'])
            ->tab(__('backend.brand_settings.tabs.legal'));

        CRUD::field('general_conditions')
            ->label(__('backend.brand_settings.general_conditions'))
            ->type('ckeditor')
            //->extraPlugins(['oembed'])
            ->tab(__('backend.brand_settings.tabs.legal'));

        CRUD::field('gdpr_text')
            ->label(__('backend.brand_settings.gdpr_text'))
            ->type('ckeditor')
            //->extraPlugins(['oembed'])
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

    public function update()
    {
        DB::beginTransaction();

        try {
            // El Form Request ya valida permisos, pero doble verificación
            if (!$this->isSuperuser()) {
                // Eliminar campos peligrosos del request si no es superuser
                request()->request->remove('custom_script');
                request()->request->remove('aux_code');
            }

            // Verificar que se está editando la brand correcta
            $brandId = request()->route('id');
            if ($brandId != get_current_brand_id()) {
                throw new \Exception(__('backend.brand_settings.errors.unauthorized_brand_edit'));
            }

            // Actualizar la brand
            $response = $this->traitUpdate();

            // Sincronizar register inputs
            $this->syncRegisterInputs($this->crud->entry);

            // Todo OK, hacer commit
            DB::commit();

            // Mostrar mensaje de éxito
            Alert::success(__('backend.brand_settings.messages.update_success'))->flash();

            return $response;
        } catch (\Exception $e) {
            DB::rollback();

            // Log del error
            Log::error('Error updating brand settings', [
                'brand_id' => $brandId ?? null,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Mostrar error al usuario
            Alert::error(__('backend.brand_settings.messages.update_error', ['error' => $e->getMessage()]))->flash();

            return redirect()->back()->withInput();
        }
    }

    private function syncRegisterInputs($brand)
    {
        $data = request()->input('input', []);

        // Obtener IDs válidos de register inputs
        $validInputIds = RegisterInput::pluck('id')->toArray();

        $attach = [];
        foreach ($data as $item) {
            // Validar que el ID existe
            if (!isset($item['id']) || !in_array($item['id'], $validInputIds)) {
                Log::warning('Invalid register input ID attempted', [
                    'brand_id' => $brand->id,
                    'attempted_id' => $item['id'] ?? 'null'
                ]);
                continue;
            }

            // Solo sincronizar si está activo
            if (!empty($item['active'])) {
                $attach[$item['id']] = [
                    'required' => !empty($item['required']),
                ];
            }
        }

        // Sincronizar con la base de datos
        $brand->register_inputs()->sync($attach);
    }
}
