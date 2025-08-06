<?php

namespace App\Http\Controllers\Admin;

use DB;
use App\Models\User;
use App\Models\Brand;
use App\Models\Event;
use App\Models\Taxonomy;
use App\Scopes\BrandScope;
use App\Traits\AllowUsersTrait;
use App\Observers\EventObserver;
use Prologue\Alerts\Facades\Alert;
use App\Traits\CrudPermissionTrait;
use Illuminate\Support\Facades\Log;
use App\Uploaders\WebpImageUploader;
use Backpack\CRUD\app\Library\Widget;
use App\Http\Requests\EventCrudRequest;
use Illuminate\Support\Facades\Storage;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class EventCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation { destroy as traitDestroy;
    }
    use \Backpack\Pro\Http\Controllers\Operations\DropzoneOperation;
    use CrudPermissionTrait;
    use AllowUsersTrait;

    public function setup()
    {
        CRUD::setModel(Event::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/event');
        CRUD::setEntityNameStrings(__('backend.menu.event'), __('backend.menu.events'));
        $this->setAccessUsingPermissions();

        CRUD::addClause('ordered');

        CRUD::addButtonFromModelFunction('line', 'create_session', 'getCreateSessionButton', 'end');
        CRUD::addButtonFromModelFunction('line', 'clone', 'getCloneButton', 'end');
        CRUD::addButtonFromModelFunction('line', 'show_event', 'getShowEventButton', 'end');

    }

    protected function setupListOperation()
    {

        CRUD::setOperationSetting('lineButtonsAsDropdown', true);

        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.events.eventname'),
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere(DB::raw('lower(name)'), 'like', '%' . strtolower($searchTerm) . '%');
            }
        ]);

        CRUD::addColumn([
            'name' => 'publish_on',
            'label' => __('backend.events.publish_on'),
            'type' => 'date.str'
        ]);

        CRUD::addColumn([
            'name' => 'first_session',
            'label' => __('backend.events.firstsession'),
            'type' => 'closure',
            'function' => fn($entry) => optional($entry->firstSession)->starts_on ?? '-',
        ]);

        CRUD::addColumn([
            'name' => 'last_session',
            'label' => __('backend.events.lastsession'),
            'type' => 'closure',
            'function' => fn($entry) => optional($entry->lastSession)->starts_on ?? '-',
        ]);

        CRUD::addColumn([
            'label' => __('backend.events.createdby'),
            'type' => 'select',
            'name' => 'user_id',
            'entity' => 'user',
            'attribute' => 'email',
            'model' => User::class,
        ]);

        // filtros
        CRUD::addFilter(
            [
                'name' => 'show_incoming_events',
                'type' => 'simple',
                'label' => trans('backend.events.show-incoming-events')
            ],
            false,
            fn() => CRUD::addClause('fromNow')
        );

        CRUD::addFilter([
            'name' => 'published',
            'type' => 'simple',
            'label' => trans('backend.events.published'),
        ], false, function () {
            CRUD::addClause('published');
        });


    }

    protected function setupShowOperation(): void
    {
        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.events.eventname'),
            'type' => 'text',
        ]);


        CRUD::addColumn([
            'name' => 'publish_on',
            'label' => __('backend.events.publish_on'),
            'type' => 'date'
        ]);

        CRUD::addColumn([
            'name' => 'first_session',
            'label' => __('backend.events.firstsession'),
            'type' => 'closure',
            'function' => fn($entry) => optional($entry->firstSession)->starts_on ?? '-',
        ]);

        CRUD::addColumn([
            'name' => 'last_session',
            'label' => __('backend.events.lastsession'),
            'type' => 'closure',
            'function' => fn($entry) => optional($entry->lastSession)->starts_on ?? '-',
        ]);

        CRUD::addColumn([
            'label' => __('backend.events.createdby'), // tu traducción personalizada
            'type' => 'relationship',
            'name' => 'user',
            'attribute' => 'email',
        ]);

        CRUD::addColumn([
            'name' => 'lead',
            'type' => 'text',
            'label' => __('backend.events.lead'),
        ]);

        CRUD::addColumn([
            'name' => 'description',
            'label' => __('backend.events.eventdescription'),
            'type' => 'ckeditor',
            'escaped' => false,
        ]);

        CRUD::addColumn([
            'name' => 'metadata',
            'label' => __('backend.events.eventmetada'),
            'type' => 'ckeditor',
        ]);

        CRUD::addColumn([
            'name' => 'slug',
            'type' => 'slug',
            'target' => 'name',
            'label' => __('backend.events.slug'),
        ]);



        CRUD::addColumn([
            'name' => 'image',
            'label' => __('backend.events.posterimage'),
            'type' => 'image',
            /* 'prefix' => 'storage/', */
            'disk' => 'public',
            'height' => '100px',
        ]);

        CRUD::addColumn([
            'name' => 'banner',
            'label' => __('backend.events.banner'),
            'type' => 'image',
            'prefix' => 'storage/',
            'height' => '100px',
        ]);

        CRUD::addColumn([
            'name' => 'email',
            'label' => __('backend.events.responsibleemail'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'phone',
            'label' => __('backend.events.responsiblephone'),
        ]);

        CRUD::addColumn([
            'name' => 'site',
            'label' => __('backend.events.website'),
        ]);

        CRUD::addColumn([
            'name' => 'social',
            'label' => __('backend.events.socialaccounts'),
            'type' => 'table',
            'entity_singular' => 'social',
            'columns' => [
                'name' => __('backend.events.name'),
                'desc' => __('backend.events.link'),
            ],
        ]);

        CRUD::addColumn([
            'name' => 'tags',
            'label' => __('backend.events.tags'),
            'type' => 'closure',
            'function' => function ($entry) {
                $tags = $entry->getTranslation('tags', app()->getLocale());

                if (is_string($tags)) {
                    $decoded = json_decode($tags, true);
                    if (is_array($decoded)) {
                        return implode(', ', $decoded);
                    }
                } elseif (is_array($tags)) {
                    return implode(', ', $tags);
                }

                return '-';
            },
        ]);

        CRUD::addColumn([
            'name' => 'images',
            'label' => __('backend.events.extra_images'),
            'type' => 'closure',
            'escaped' => false,
            'function' => function ($entry) {
                if (empty($entry->images))
                    return '-';

                $output = '<div style="display:flex; gap:10px; flex-wrap:wrap;">';

                foreach ($entry->images as $img) {
                    $url = asset('storage/' . ltrim($img, '/'));
                    $output .= '<img src="' . $url . '" style="max-height:100px; border-radius:4px;" />';
                }

                $output .= '</div>';
                return $output;
            }
        ]);

        CRUD::addColumn([
            'name' => 'custom_logo',
            'label' => __('backend.events.custom_logo'),
            'type' => 'image',
            'prefix' => 'storage/',
            'height' => '100px',
        ]);

        CRUD::addColumn([
            'name' => 'custom_text',
            'label' => __('backend.events.custom_text'),
            'type' => 'ckeditor',
        ]);

        CRUD::addColumn([
            'name' => 'show_calendar',
            'label' => __('backend.events.show_calendar'),
            'type' => 'boolean',
            'options' => [
                0 => 'No',
                1 => 'Si',
            ],
        ]);

        CRUD::addColumn([
            'name' => 'full_width_calendar',
            'label' => __('backend.events.full_width_calendar'),
            'type' => 'boolean',
            'options' => [
                0 => 'No',
                1 => 'Si',
            ],
        ]);

        CRUD::addColumn([
            'name' => 'hide_exhausted_sessions',
            'label' => __('backend.events.hide_exhausted_sessions'),
            'type' => 'boolean',
            'options' => [
                0 => 'No',
                1 => 'Si',
            ],
        ]);

        CRUD::addColumn([
            'name' => 'enable_gift_card',
            'label' => __('backend.events.enable_gift_cards'),
            'type' => 'boolean',
            'options' => [
                0 => 'No',
                1 => 'Si',
            ],
        ]);

        CRUD::addColumn([
            'name' => 'price_gift_card',
            'label' => __('backend.events.price_gift_card'),
            'type' => 'number',
        ]);

        CRUD::addColumn([
            'name' => 'gift_card_text',
            'label' => __('backend.events.gift_card_text'),
            'type' => 'ckeditor',
        ]);

        CRUD::addColumn([
            'name' => 'gift_card_email_text',
            'label' => __('backend.events.gift_card_email_text'),
            'type' => 'ckeditor',
        ]);

        CRUD::addColumn([
            'name' => 'gift_card_legal_text',
            'label' => __('backend.events.gift_card_legal'),
            'type' => 'ckeditor',
        ]);

        CRUD::addColumn([
            'name' => 'gift_card_footer_text',
            'label' => __('backend.events.gift_card_footer_text'),
            'type' => 'ckeditor',
        ]);

        CRUD::addColumn([
            'name' => 'validate_all_event',
            'label' => __('backend.events.validate_all_event'),
            'type' => 'boolean',
            'options' => [
                0 => 'No',
                1 => 'Si',
            ],
        ]);

        // Mostrar sesiones (tabla personalizada)
        CRUD::addColumn([
            'name' => 'sessions_order',
            'label' => __('backend.events.sessions'),
            'type' => 'view',
            'view' => 'vendor.backpack.crud.event.sessions_table',
        ]);

        // Subblade de ventas por evento
        CRUD::addColumn([
            'name' => 'event_sales',
            'label' => __('backend.events.sales'),
            'type' => 'view',
            'view' => 'vendor.backpack.crud.event.sales_table',
        ]);

    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(EventCrudRequest::class);
        $this->setBasicTab();
        $this->setExtraTab();
        $this->setInscriptionsTab();
        $this->setEntradaTab();
        $this->setGiftTab();
        $this->setCalendarioTab();
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();

    }

    public function store(EventCrudRequest $request)
    {
        if (is_string($request->images)) {
            $decoded = json_decode($request->images, true);

            // Si es un array asociativo, pasamos a array plano
            if (is_array($decoded)) {
                $request->merge([
                    'images' => array_values($decoded)
                ]);
            }
        }

        $response = $this->traitStore();
        $event = $this->crud->getCurrentEntry();

        EventObserver::processImages($event);

        $taxonomies = json_decode($request->get('taxonomies'), true) ?? [];

        foreach (get_current_brand()->partnershipedBrands as $partner) {
            $partnerKey = 'taxonomies_alt_' . $partner->id;
            $partnerTaxonomies = json_decode($request->get($partnerKey), true) ?? [];
            $taxonomies = array_merge($taxonomies, $partnerTaxonomies);
        }

        $seasons_id = get_current_brand()->extra_config['seasons_taxonomy_id'] ?? null;
        if ($seasons_id) {
            $seasonKey = 'taxonomies_alt_' . $seasons_id;
            $seasonTaxonomies = json_decode($request->get($seasonKey), true) ?? [];
            $taxonomies = array_merge($taxonomies, $seasonTaxonomies);
        }

        $extraTaxonomies = json_decode($request->get('allTaxonomies'), true) ?? [];
        $taxonomies = array_merge($taxonomies, $extraTaxonomies);

        $this->crud->entry->allTaxonomies()->sync(array_map('intval', $taxonomies));

        return $response;
    }


    public function update(EventCrudRequest $request)
    {
        $event = $this->crud->getCurrentEntry();
        $oldImages = $event->images ?? [];

        // Normalizamos `$request->images` (stdClass → array, JSON-string → array)
        if ($request->images instanceof \stdClass) {
            $arrayFromObject = array_values((array) $request->images);
            $request->merge(['images' => $arrayFromObject]);
            Log::info('[Debug][SessionUpdate] Normalizado stdClass → array: ' . json_encode($arrayFromObject));
        }
        if (is_string($request->images)) {
            $decoded = json_decode($request->images, true);
            if (is_array($decoded)) {
                $arrayFromJson = array_values($decoded);
                $request->merge(['images' => $arrayFromJson]);
                Log::info('[Debug][SessionUpdate] Normalizado JSON-string → array: ' . json_encode($arrayFromJson));
            }
        }


        $response = $this->traitUpdate();

        $taxonomies = json_decode($request->get('taxonomies'), true) ?? [];

        foreach (get_current_brand()->partnershipedBrands as $partner) {
            $partnerKey = 'taxonomies_alt_' . $partner->id;
            $partnerTaxonomies = json_decode($request->get($partnerKey), true) ?? [];
            $taxonomies = array_merge($taxonomies, $partnerTaxonomies);
        }

        $seasons_id = get_current_brand()->extra_config['seasons_taxonomy_id'] ?? null;
        if ($seasons_id) {
            $seasonKey = 'taxonomies_alt_' . $seasons_id;
            $seasonTaxonomies = json_decode($request->get($seasonKey), true) ?? [];
            $taxonomies = array_merge($taxonomies, $seasonTaxonomies);
        }

        $extraTaxonomies = json_decode($request->get('allTaxonomies'), true) ?? [];
        $taxonomies = array_merge($taxonomies, $extraTaxonomies);

        $this->crud->entry->allTaxonomies()->sync(array_map('intval', $taxonomies));

        $event = $this->crud->getCurrentEntry();

        EventObserver::processImages($event);

        //  Volvemos a leer `$session->images` ya procesadas (definitivas)
        $event->refresh();
        $newImages = $event->images ?? [];

        // Calculamos qué imágenes antiguas hay que eliminar del disco
        $toDelete = array_diff($oldImages, $newImages);

        foreach ($toDelete as $oldPath) {
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            } else {
                Log::warning('[Debug][SessionUpdate] No existe en disco, no se pudo borrar: ' . $oldPath);
            }
        }

        return $response;
    }

    public function clone($id)
    {
        $original = $this->crud->model->findOrFail($id);
        $cloned = $original->replicate();
        $cloned->name = $original->name . ' (Copia)';
        $cloned->slug = $original->slug . '-' . uniqid();
        $cloned->save();

        // Definimos campos a copiar y su ruta base
        $imageFields = ['image', 'banner', 'custom_logo'];
        $basePath = 'uploads/' . get_current_brand()->code_name . '/event/';

        foreach ($imageFields as $field) {
            $originalPath = $original->{$field};

            if ($originalPath && Storage::disk('public')->exists($originalPath)) {
                $filename = pathinfo($originalPath, PATHINFO_FILENAME);
                $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
                $newFilename = "{$field}-clone-" . uniqid() . '.' . $extension;
                $newPath = $basePath . $cloned->id . '/' . $newFilename;

                // Copiamos físicamente el archivo
                Storage::disk('public')->copy($originalPath, $newPath);

                // Asignamos la nueva ruta al clon
                $cloned->{$field} = $newPath;
            }
        }

        $cloned->save();

        $cloned->allTaxonomies()->sync($original->allTaxonomies->pluck('id')->toArray());

        Alert::success('Evento clonado correctamente')->flash();

        return redirect(backpack_url("event/{$cloned->id}/edit"));
    }



    protected function setBasicTab()
    {
        CRUD::addField([
            'name' => 'name',
            'type' => 'text',
            'label' => __('backend.events.eventname'),
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12 col-sm-6',
            ],
            'tab' => __('backend.events.tab_basic'),
        ]);

        CRUD::addField([
            'name' => 'slug',
            'type' => 'slug',
            'target' => 'name',
            'label' => __('backend.events.slug'),
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12 col-sm-6',
            ],
            'tab' => __('backend.events.tab_basic'),
        ]);

        CRUD::addField([
            'name' => 'lead',
            'type' => 'text',
            'label' => __('backend.events.lead'),
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12 col-sm-6',
            ],
            'tab' => __('backend.events.tab_basic'),
        ]);

        CRUD::addField([
            'name' => 'publish_on',
            'label' => __('backend.events.publish'),
            'type' => 'datetime_picker',
            'datetime_picker_options' => [
                'format' => 'DD/MM/YYYY HH:mm',
                'language' => 'ca',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12 col-sm-6',
            ],
            'tab' => __('backend.events.tab_basic'),
        ]);

        CRUD::addField([
            'name' => 'image',
            'label' => __('backend.events.posterimage'),
            'type' => 'image',
            'crop' => true,
            'aspect_ratio' => 1.78,
            'upload' => true,
            'withFiles' => [
                'disk' => 'public',
                'uploader' => WebpImageUploader::class,
                'path' => 'uploads/' . get_current_brand()->code_name . '/event/' . ($this->crud->getCurrentEntry()?->id ?? '__TEMP__'),
                'resize' => [
                    'max' => 1200,
                ],
            ],
            'tab' => __('backend.events.tab_basic'),
        ]);

        $this->addTagField();

        // if brand is Promotor will not have own Taxonomies. So we hidden this

        $extra = get_current_brand()->extra_config;
        $seasons_id = $extra['seasons_taxonomy_id'] ?? null;
        $main = $extra['main_taxonomy_id'];

        if (get_brand_capability() == 'basic' && $seasons_id) {
            CRUD::addField([
                'label' => __('backend.events.seasons'),
                'type' => 'checklist_from_builder',
                'name' => "taxonomies_alt_$seasons_id",
                'alt_value' => 'taxonomies_alt',
                'entity' => 'allTaxonomies',
                'attribute' => 'name',
                'builder' => Taxonomy::query()->whereParentId($seasons_id),
                'hint' => '<span class="small">' . __('tincket/backend.events.help-seasons-select') . '</span>',
                'tab' => __('backend.events.tab_basic'),
            ]);
        }

        // if brand is Promotor will not have own Taxonomies. So we hidden this 
        if (get_brand_capability() === 'basic' && $main) {
            CRUD::addField([
                'label' => __('backend.events.taxonomies'),
                'type' => 'checklist',
                'name' => 'taxonomies',
                'options' => function ($query) use ($main) {
                    return $query
                        ->whereParentId($main)
                        ->pluck('name', 'id')
                        ->toArray();
                },
                'hint' => __('backend.events.help-taxonomies-select'),
                'tab' => __('backend.events.tab_basic'),
            ]);
        }

        foreach (get_current_brand()->partnershipedBrands as $partner) {

            $mainId = $partner->extra_config['main_taxonomy_id'] ?? null;

            CRUD::addField([
                'label' => __('backend.events.taxonomies') . " {$partner->name}",
                'type' => 'checklist',
                'name' => 'allTaxonomies',
                'entity' => 'allTaxonomies',
                'attribute' => 'name',
                'model' => Taxonomy::class,
                'options' => function ($query) use ($partner, $mainId) {
                    return $query
                        ->withoutGlobalScope(BrandScope::class)
                        ->where('brand_id', $partner->id)
                        ->where('parent_id', $mainId)
                        ->pluck('name', 'id')
                        ->toArray();
                },
                'hint' => __('backend.events.help-taxonomies-select'),
                'tab' => __('backend.events.tab_basic'),
            ]);
        }

        CRUD::addField([
            'name' => 'description',
            'label' => __('backend.events.eventdescription'),
            'type' => 'ckeditor',
            'wrapperAttributes' => [],
            'tab' => __('backend.events.tab_basic'),
        ]);

        CRUD::addField([
            'name' => 'metadata',
            'label' => __('backend.events.eventmetada'),
            'type' => 'ckeditor',
            'wrapperAttributes' => [],
            'tab' => __('backend.events.tab_basic'),
        ]);
    }

    public function setExtraTab()
    {
        CRUD::addField([
            'name' => 'images',
            'label' => __('backend.events.extra_images'),
            'type' => 'dropzone',
            'upload' => true,
            'disk' => 'public',
            'prefix' => 'uploads/'
                . get_current_brand()->code_name
                . '/event/'
                . ($this->crud->getCurrentEntry()?->id ?? '__TEMP__')
                . '/',
            'tab' => __('backend.events.tab_extra'),
        ]);

        CRUD::addField([
            'name' => 'email',
            'label' => __('backend.events.responsibleemail'),
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12 col-sm-6',
            ],
            'tab' => __('backend.events.tab_extra'),
        ]);

        CRUD::addField([
            'name' => 'phone',
            'label' => __('backend.events.responsiblephone'),
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12 col-sm-6',
            ],
            'tab' => __('backend.events.tab_extra'),
        ]);

        CRUD::addField([
            'name' => 'site',
            'label' => __('backend.events.website'),
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12 col-sm-6',
            ],
            'tab' => __('backend.events.tab_extra'),
        ]);

        CRUD::addField([
            'name' => 'social',
            'label' => __('backend.events.socialaccounts'),
            'type' => 'table',
            'entity_singular' => 'social',
            'columns' => [
                'name' => __('backend.events.name'),
                'desc' => __('backend.events.link'),
            ],
            'max' => 10,
            'min' => 0,
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12',
            ],
            'tab' => __('backend.events.tab_extra'),
        ]);
    }

    public function setInscriptionsTab()
    {
        CRUD::addField([
            'name' => 'validate_all_event',
            'label' => __('backend.events.validate_all_event'),
            'type' => 'switch',
            'tab' => __('backend.events.tab_inscriptions'),
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12',
            ],
        ]);

        CRUD::addField([
            'name' => 'validate_all_event_hint',
            'type' => 'custom_html',
            'value' => '<div class="alert alert-success">' . __('backend.events.validate_all_event_hint') . '</div>',
            'tab' => __('backend.events.tab_inscriptions'),
        ]);
    }

    public function setEntradaTab()
    {
        CRUD::addField([
            'name' => 'custom_text',
            'label' => __('backend.events.custom_text'),
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12',
            ],
            'tab' => __('backend.events.tab_ticket'),
        ]);

        CRUD::addField([
            'name' => 'custom_logo',
            'label' => __('backend.events.custom_logo'),
            'type' => 'image',
            'upload' => false,
            'crop' => true,
            'aspect_ratio' => 1.78,
            'withFiles' => [
                'disk' => 'public',
                'path' => 'uploads/' . get_current_brand()->code_name . '/event/' . ($this->crud->getCurrentEntry()?->id ?? '__TEMP__'),
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12',
            ],
            'tab' => __('backend.events.tab_ticket'),
        ]);

        CRUD::addField([
            'name' => 'banner',
            'label' => __('backend.events.banner'),
            'type' => 'image',
            'upload' => false,
            'crop' => true,
            //'aspect_ratio' => 1.78,
            'withFiles' => [
                'disk' => 'public',
                'path' => 'uploads/' . get_current_brand()->code_name . '/event/' . ($this->crud->getCurrentEntry()?->id ?? '__TEMP__'),
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12',
            ],
            'tab' => __('backend.events.tab_ticket'),
        ]);

        CRUD::addField([
            'name' => 'separator',
            'type' => 'custom_html',
            'value' => '<div class="alert alert-danger">' . __('backend.events.banner_info') . '</div>',
            'tab' => __('backend.events.tab_ticket')
        ]);
    }

    public function setGiftTab()
    {
        CRUD::addField([
            'name' => 'enable_gift_card',
            'label' => __('backend.events.enable_gift_cards'),
            'type' => 'switch',
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12 col-sm-6',
            ],

            'tab' => __('backend.events.tab_gift'),
        ]);

        CRUD::addField([
            'name' => 'price_gift_card',
            'label' => __('backend.events.price_gift_card'),
            'type' => 'number',
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12 col-sm-6',
            ],
            'attributes' => ["step" => "any"],
            'tab' => __('backend.events.tab_gift'),
        ]);

        CRUD::addField([
            'name' => 'gift_card_text',
            'label' => __('backend.events.gift_card_text'),
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12',
            ],
            'tab' => __('backend.events.tab_gift'),
            'hint' => __('backend.events.gift_card_email_text_hint'),
        ]);

        CRUD::addField([
            'name' => 'gift_card_footer_text',
            'label' => __('backend.events.gift_card_footer_text'),
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12',
            ],
            'tab' => __('backend.events.tab_gift'),
            'hint' => __('backend.events.gift_card_email_text_hint'),
        ]);

        CRUD::addField([
            'name' => 'gift_card_email_text',
            'label' => __('backend.events.gift_card_email_text'),
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12',
            ],
            'tab' => __('backend.events.tab_gift'),
            'hint' => __('backend.events.gift_card_email_text_hint'),
        ]);

        CRUD::addField([
            'name' => 'gift_card_legal_text',
            'label' => __('backend.events.gift_card_legal_text'),
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12',
            ],
            'tab' => __('backend.events.tab_gift'),

        ]);

        Widget::add([
            'type' => 'view',
            'view' => 'vendor.backpack.crud.inc.gift_card_script',
        ]);

    }

    public function setCalendarioTab()
    {
        CRUD::addField([
            'name' => 'show_calendar',
            'type' => 'switch',
            'label' => __('backend.events.show_calendar'),
            'tab' => 'Calendario',
        ]);

        CRUD::addField([
            'name' => 'full_width_calendar',
            'type' => 'switch',
            'label' => __('backend.events.full_width_calendar'),
            'tab' => 'Calendario',
        ]);

        CRUD::addField([
            'name' => 'hide_exhausted_sessions',
            'type' => 'switch',
            'label' => __('backend.events.hide_exhausted_sessions'),
            'tab' => 'Calendario',
        ]);
    }

    protected function addTagField()
    {
        $entry = $this->crud->getCurrentEntry();

        $rawTags = $entry ? $entry->getTranslation('tags', app()->getLocale()) : null;

        if (is_array($rawTags)) {
            $currentTags = $rawTags;
        } elseif (is_string($rawTags)) {
            $decoded = json_decode($rawTags, true);
            $currentTags = is_array($decoded) ? $decoded : [];
        } else {
            $currentTags = [];
        }

        $tagOptions = $currentTags ? array_combine($currentTags, $currentTags) : [];

        CRUD::addField([
            'name' => 'tags',
            'label' => __('backend.events.tags'),
            'type' => 'select2_from_array',
            'options' => $tagOptions,     // las opciones que conoce Select2
            'value' => $currentTags,    // las que están “selected” al cargar
            'allows_null' => true,
            'allows_multiple' => true,
            'attributes' => [
                'data-tags' => 'true',
                'data-token-separators' => '[","," "]',
            ],
            'tab' => __('backend.events.tab_basic'),
        ]);
    }

}
