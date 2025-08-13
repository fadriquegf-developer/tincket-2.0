<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Event;
use App\Models\Space;
use App\Models\Session;
use App\Models\Inscription;
use App\Models\SessionSlot;
use App\Models\SessionTempSlot;
use Illuminate\Support\Facades\DB;
use App\Uploaders\WebpImageUploader;
use App\Http\Requests\SessionRequest;
use Backpack\CRUD\app\Library\Widget;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

trait SessionCrudUi
{
    protected function setupShowOperation()
    {
        CRUD::addColumn([
            'name' => 'visibility',
            'label' => __('backend.session.visibility'),
            'type' => 'boolean',
        ]);

        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.session.title'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'label' => __('backend.session.event'),
            'type' => 'select',
            'name' => 'event_id',
            'entity' => 'event',
            'attribute' => 'name',
            'model' => Event::class,
        ]);

        CRUD::addColumn([
            'label' => __('backend.session.space'),
            'type' => 'select',
            'name' => 'space_id',
            'entity' => 'space',
            'attribute' => 'name',
            'model' => Space::class,
        ]);

        CRUD::addColumn([
            'name' => 'starts_on',
            'label' => __('backend.session.startson'),
            'type' => 'datetime',
        ]);

        CRUD::addColumn([
            'name' => 'ends_on',
            'label' => __('backend.session.endson'),
            'type' => 'datetime',
        ]);

        CRUD::addColumn([
            'name' => 'inscription_starts_on',
            'label' => __('backend.session.inscriptionstarts'),
            'type' => 'datetime',
        ]);

        CRUD::addColumn([
            'name' => 'inscription_ends_on',
            'label' => __('backend.session.inscriptionends'),
            'type' => 'datetime',
        ]);

        CRUD::addColumn([
            'name' => 'user_id',
            'label' => __('backend.session.createdby'),
            'type' => 'select',
            'entity' => 'user',
            'attribute' => 'email',
            'model' => User::class,
        ]);

        CRUD::addColumn([
            'name' => 'tpv_id',
            'label' => 'TPV',
            'type' => 'number',
        ]);

        CRUD::addColumn([
            'name' => 'is_numbered',
            'label' => __('backend.session.numbered'),
            'type' => 'boolean',
        ]);

        CRUD::addColumn([
            'name' => 'slug',
            'label' => __('backend.session.slug'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'description',
            'label' => __('backend.session.description'),
            'type' => 'textarea',
        ]);

        CRUD::addColumn([
            'name' => 'metadata',
            'label' => __('backend.session.sessionmetadata'),
            'type' => 'textarea',
        ]);

        CRUD::addColumn([
            'name' => 'max_places',
            'label' => __('backend.session.maximumplaces'),
            'type' => 'number',
        ]);

        CRUD::addColumn([
            'name' => 'tags',
            'label' => __('backend.events.tags'),
            'type' => 'array',
        ]);

        CRUD::addColumn([
            'name' => 'images',
            'label' => __('backend.session.extraimages'),
            'type' => 'upload_multiple',
            'disk' => 'public',
        ]);

        CRUD::addColumn([
            'name' => 'external_url',
            'label' => __('backend.session.external_url'),
            'type' => 'url',
        ]);

        CRUD::addColumn([
            'name' => 'autolock_type',
            'label' => __('backend.session.autolock_type'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'autolock_n',
            'label' => __('backend.session.autolock_n'),
            'type' => 'number',
        ]);

        CRUD::addColumn([
            'name' => 'limit_x_100',
            'label' => __('backend.session.limit_x_100'),
            'type' => 'number',
            'suffix' => '%',
        ]);

        CRUD::addColumn([
            'name' => 'private',
            'label' => __('backend.session.private'),
            'type' => 'boolean',
        ]);

        CRUD::addColumn([
            'name' => 'only_pack',
            'label' => __('backend.session.only_pack'),
            'type' => 'boolean',
        ]);

        CRUD::addColumn([
            'name' => 'session_color',
            'label' => __('backend.session.session_color'),
            'type' => 'color',
        ]);

        CRUD::addColumn([
            'name' => 'session_bg_color',
            'label' => __('backend.session.session_bg_color'),
            'type' => 'color',
        ]);

        CRUD::addColumn([
            'name' => 'custom_logo',
            'label' => __('backend.session.custom_logo'),
            'type' => 'image',
            'disk' => 'public',
        ]);

        CRUD::addColumn([
            'name' => 'banner',
            'label' => __('backend.session.banner'),
            'type' => 'image',
            'disk' => 'public',
        ]);

        CRUD::addColumn([
            'name' => 'code_type',
            'label' => __('backend.session.code_type'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'validate_all_session',
            'label' => __('backend.session.validate_all_session'),
            'type' => 'boolean',
        ]);
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'visibility',
            'label' => '',
            'type' => 'closure',
            'function' => function ($entry) {
                if ($entry->visibility == 1) {
                    return '<i class="la la-circle" title="' . __('backend.session.visibility') . '" aria-hidden="true" style="color:green;"></i>';
                } else {
                    return '<i class="la la-circle" title="' . __('backend.session.no-visibility') . '" aria-hidden="true" style="color:red;"></i>';
                }
            },
            'escaped' => false,
        ]);

        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.session.sessionname'),
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere(DB::raw('lower(name)'), 'like', '%' . strtolower($searchTerm) . '%');
            }
        ]);

        CRUD::addColumn([
            'label' => __('backend.session.event'),
            'type' => 'select',
            'name' => 'event_id',
            'entity' => 'event',
            'attribute' => 'name',
            'model' => Event::class,
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('event', function ($q) use ($column, $searchTerm) {
                    $q->where(DB::raw('lower(name)'), 'like', '%' . strtolower($searchTerm) . '%');
                });
            }
        ]);

        CRUD::addColumn([
            'label' => __('backend.session.space'),
            'type' => 'select',
            'name' => 'space_id',
            'entity' => 'space',
            'attribute' => 'name',
            'model' => Space::class,
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('space', function ($q) use ($column, $searchTerm) {
                    $q->where(DB::raw('lower(name)'), 'like', '%' . strtolower($searchTerm) . '%');
                });
            }
        ]);

        CRUD::addColumn([
            'name' => 'starts_on',
            'label' => __('backend.session.startson'),
            'type' => 'date.str'
        ]);

        CRUD::addColumn([
            'name' => 'ends_on',
            'label' => __('backend.session.endson'),
            'type' => 'date.str'
        ]);

        CRUD::addColumn([
            'name' => 'inscription_starts_on',
            'label' => __('backend.session.inscriptionstarts'),
            'type' => 'date.str'
        ]);

        CRUD::addColumn([
            'label' => __('backend.session.createdby'),
            'type' => 'select',
            'name' => 'user_id',
            'entity' => 'user',
            'attribute' => 'email',
            'model' => User::class,
        ]);

        $this->setupFilters();

        Widget::add()->to('after_content')->type('view')->view('core.session.add-sessions-modal');
        CRUD::setOperationSetting('lineButtonsAsDropdown', true);
        CRUD::addButtonFromView('top', 'multi_create', 'buttons.multi_create', 'end');
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(SessionRequest::class);
        Widget::add()->type('script')->content(asset('js/session-fill-max-places.js'));

        $this->setBasicTab();
        $this->setInscriptionsTab();
        $this->setRatesTab();
        $this->setTicketTab();
        $this->setCodesTab();
    }


    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();

        $session = $this->crud->getCurrentEntry();
        if ($session) {
            $space = $session->space;

            if ($session->is_numbered && $space && $space->svg_path && \Storage::disk('public')->exists($space->svg_path)) {
                $this->setLayoutTab($space, $session->id);
            }

            // Si hay ventas, deshabilitar el campo tpv
            if ($session->getSelledInscriptions(false)) {
                if (isset($this->crud->getCurrentFields()['tpv_id'])) {
                    $field = $this->crud->getCurrentFields()['tpv_id'];
                    $field['attributes']['disabled'] = 'disabled';
                    $this->crud->addField($field);
                }
            }
        }
    }

    // Modificar el método setLayoutTab en el trait SessionCrudUi

    private function setLayoutTab($space, $session_id)
    {
        // 1. Obtener TODOS los slots del espacio con sus propiedades completas
        $spaceSlots = $space->slots->keyBy('id');

        // 2. Crear el mapa base con los estados heredados del espacio
        $slots_map = $spaceSlots->map(function ($slot) {
            return [
                'id' => $slot->id,
                'name' => $slot->name,
                'status_id' => $slot->status_id, // ← IMPORTANTE: heredar el status del espacio
                'comment' => $slot->comment,     // ← También heredar el comentario
                'x' => $slot->x,
                'y' => $slot->y,
                'zone_id' => $slot->zone_id,
            ];
        });

        // 3. Sobrescribir con los slots bloqueados manualmente en la sesión (SessionSlot)
        $sessionSlots = SessionSlot::where('session_id', $session_id)->get();

        if ($sessionSlots->count() > 0) {
            foreach ($sessionSlots as $sessionSlot) {
                // Si existe un SessionSlot, sobrescribe los valores del slot del espacio
                if ($slots_map->has($sessionSlot->slot_id)) {
                    $slots_map[$sessionSlot->slot_id] = array_merge(
                        $slots_map[$sessionSlot->slot_id],
                        [
                            'status_id' => $sessionSlot->status_id,
                            'comment' => $sessionSlot->comment,
                            'type' => 'SessionSlot', // Para identificar que viene de la sesión
                        ]
                    );
                }
            }
        }

        // 4. Sobrescribir con los slots bloqueados automáticamente (autolocks confirmados)
        $autolocks = SessionTempSlot::whereNull('expires_on')
            ->where('session_id', $session_id)
            ->get();

        if ($autolocks->count() > 0) {
            foreach ($autolocks as $autolock) {
                if ($slots_map->has($autolock->slot_id)) {
                    $slots_map[$autolock->slot_id] = array_merge(
                        $slots_map[$autolock->slot_id],
                        [
                            'status_id' => $autolock->status_id,
                            'comment' => trans('backend.session.autolock_type'),
                            'type' => 'Autolock',
                        ]
                    );
                }
            }
        }

        // 5. Marcar los slots vendidos (tienen prioridad máxima)
        $soldSlots = Inscription::paid()
            ->where('session_id', $session_id)
            ->whereNotNull('slot_id')
            ->get();

        if ($soldSlots->count() > 0) {
            foreach ($soldSlots as $inscription) {
                if ($slots_map->has($inscription->slot_id)) {
                    $slots_map[$inscription->slot_id] = array_merge(
                        $slots_map[$inscription->slot_id],
                        [
                            'status_id' => 2, // ID de estado "vendido"
                            'comment' => null, // Las vendidas no suelen tener comentario
                            'type' => 'Sold',
                        ]
                    );
                }
            }
        }

        // 6. Preparar las variables para la vista
        $this->data['slots_map'] = $slots_map->values()->all();

        // 7. Las zonas siempre vienen del espacio (con sus colores)
        $this->data['zones_map'] = $space->zones->map(function ($zone) {
            return [
                'id' => $zone->id,
                'name' => $zone->name,
                'color' => $zone->color, // ← IMPORTANTE: incluir el color de la zona
            ];
        })->values()->all();

        // 8. Log para debugging
        \Log::info("[SessionCrudUi] Preparando mapa para sesión {$session_id}", [
            'total_slots' => $slots_map->count(),
            'session_slots' => $sessionSlots->count(),
            'autolocks' => $autolocks->count(),
            'sold' => $soldSlots->count(),
            'zones' => $space->zones->count(),
        ]);

        // 9. Compartir con el blade
        \View::share('slots_map', $this->data['slots_map']);
        \View::share('zones_map', $this->data['zones_map']);

        // 10. Añadir el campo personalizado al formulario
        $this->crud->addField([
            'name' => 'svg_path',
            'label' => '',
            'type' => 'svg_layout_session',
            'tab' => 'Layout',
            'hint' => __('backend.session.hint_layout'),
        ]);
    }

    protected function setupFilters()
    {
        CRUD::addFilter(
            [
                'name' => 'show_incoming_sessions',
                'type' => 'simple',
                'label' => __('backend.events.show-incoming-sessions'),
            ],
            false,
            function () {
                CRUD::addClause('where', 'starts_on', '>=', Carbon::today());
            }
        );

        CRUD::addFilter([
            'name' => 'starts_on',
            'type' => 'date',
            'label' => __('backend.session.startsfrom'),
        ], false, function ($value) {
            CRUD::addClause('where', 'starts_on', '>=', $value);
        });


        CRUD::addFilter([
            'name' => 'event_id',
            'type' => 'select2',
            'label' => __('backend.session.event'),
        ], function () {
            return Event::query()
                ->orderBy('name', 'asc')
                ->pluck('name', 'id')
                ->toArray();
        }, function ($value) {
            CRUD::addClause('where', 'event_id', $value);
        });
    }

    private function setBasicTab()
    {
        $eventIdFromUrl = request()->query('event_id');

        CRUD::addField([
            'name' => 'event', // relación, no event_id
            'type' => 'relationship',
            'label' => __('backend.session.event'),
            'wrapperAttributes' => ['class' => 'form-group col-md-12'],
            'value' => $eventIdFromUrl ? $eventIdFromUrl : null,
            'tab' => 'Basic',
        ]);

        CRUD::addField([
            'name' => 'name',
            'type' => 'text',
            'label' => __('backend.session.title'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => 'Basic',
        ]);

        CRUD::addField([
            'name' => 'slug',
            'label' => __('backend.session.slug'),
            'type' => 'slug',
            'target' => 'name',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => 'Basic',
        ]);

        CRUD::addField([   // date_range
            'name' => 'starts_on,ends_on', // db columns for start_date & end_date
            'label' => __('backend.session.sessiondaterange'),
            'type' => 'date_range',
            'default' => [Carbon::now(), Carbon::now()],
            'date_range_options' => [
                'drops' => 'down',
                'timePicker' => true,
                'locale' => ['format' => 'DD/MM/YYYY HH:mm']
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => 'Basic',
        ]);

        CRUD::addField([
            'name' => 'space', // nombre de la relación, no el campo FK
            'label' => __('backend.session.space'),
            'type' => 'relationship',
            'attribute' => 'name_city', // lo que se muestra en el select
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => 'Basic',
        ]);

        CRUD::addField([
            'name' => 'visibility',
            'type' => 'switch',
            'label' => __('backend.session.visibility'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => 'Basic',
        ]);

        CRUD::addField([
            'name' => 'only_pack',
            'type' => 'switch',
            'label' => __('backend.session.only_pack'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => 'Basic',
        ]);

        CRUD::addField([
            'name' => 'private',
            'type' => 'switch',
            'label' => __('backend.session.private'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => 'Basic',
        ]);

        CRUD::addField([
            'name' => 'hide_n_positions',
            'type' => 'switch',
            'label' => __('backend.session.hide_n_positions'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => 'Basic',
        ]);

        $this->addTagField();

        CRUD::addField([
            'name' => 'metadata',
            'label' => __('backend.session.sessionmetadata'),
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'wrapperAttributes' => [],
            'tab' => 'Basic',
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => __('backend.session.description'),
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'wrapperAttributes' => [],
            'tab' => 'Basic',
        ]);

        // SESSION IMAGES
        CRUD::addField([
            'name' => 'images',
            'label' => __('backend.session.extraimages'),
            'type' => 'dropzone',
            'upload' => true,
            'disk' => 'public',
            'hint' => __('backend.events.minWidth'),
            'tab' => 'Basic',
        ]);
    }

    private function setInscriptionsTab()
    {
        CRUD::addField([   // date_range
            'name' => 'inscription_starts_on,inscription_ends_on',
            'label' => __('backend.session.inscriptionsdaterange'),
            'type' => 'date_range',
            'default' => [Carbon::now(), Carbon::now()],
            'date_range_options' => [
                'drops' => 'down',
                'timePicker' => true,
                'locale' => ['format' => 'DD/MM/YYYY HH:mm']
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => __('backend.session.inscriptions'),
        ]);

        CRUD::addField([
            'name' => 'external_url',
            'type' => 'text',
            'label' => __('backend.session.external_url'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => __('backend.session.inscriptions'),
        ]);

        CRUD::addField([
            'name' => 'max_places',
            'type' => 'number',
            'label' => __('backend.session.maximumplaces'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3',
            ],
            'tab' => __('backend.session.inscriptions'),
        ]);

        CRUD::addField([
            'name' => 'limit_x_100',
            'label' => __('backend.session.limit_x_100'),
            'type' => 'number',
            'attributes' => ["max" => 100, "min" => 0],
            'suffix' => "%",
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3',
            ],
            'tab' => __('backend.session.inscriptions'),
            'default' => 100
        ]);

        CRUD::addField([
            'name' => 'is_numbered',
            'label' => __('backend.session.numbered'),
            'type' => 'radio',
            'tab' => __('backend.session.inscriptions'),
            'options' => [
                0 => __('backend.session.nonumbered'),
                1 => __('backend.session.numbered')
            ],
            'default' => 0,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => array_merge([
                'ng-model' => 'basic.is_numbered'
            ], ends_with(CRUD::getRequest()->url(), 'edit')
                ? ['readonly' => 'readonly', 'disabled' => 'disabled'] : []),
            'inline' => true,
        ]);

        if (!ends_with(CRUD::getRequest()->url(), 'edit')) {
            CRUD::addField([
                'name' => 'is_numbered_info',
                'type' => 'custom_html',
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-12',
                ],
                'value' => '<p class="alert alert-danger">' . __('backend.session.is_numbered_info') . '</p>',
                'tab' => __('backend.session.inscriptions'),
            ]);
        }

        CRUD::addField([
            'name' => 'autolock_type',
            'label' => __('backend.session.autolock_type'),
            'type' => 'select_from_array',
            'options' => [
                Session::AUTOLOCK_CROSS => __('backend.session.autolock.cross'),
                Session::AUTOLOCK_RIGHT_LEFT => __('backend.session.autolock.right_left')
            ],
            'allows_null' => true,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3',
            ],
            'tab' => __('backend.session.inscriptions')
        ]);

        CRUD::addField([
            'name' => 'autolock_n',
            'label' => __('backend.session.autolock_n'),
            'type' => 'number',
            'attributes' => ['min' => 0, 'required' => 'required'],
            'default' => 0,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3',
            ],
            'tab' => __('backend.session.inscriptions'),
        ]);

        CRUD::addField([
            'name' => 'hint_autolock',
            'type' => 'custom_html',
            'value' => '<p class="help-block text-left">' . __('backend.session.hint_autolock') . '</p>',
            'tab' => __('backend.session.inscriptions'),
        ]);

        CRUD::addField([
            'name' => 'validate_all_session',
            'label' => __('backend.session.validate_all_session'),
            'type' => 'radio',
            'tab' => __('backend.session.inscriptions'),
            'options' => [
                0 => __('backend.session.individual'),
                1 => __('backend.session.group')
            ],
            'default' => 0,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-9',
            ],
            'inline' => true,
        ]);

        CRUD::addField([
            'name' => 'validate_all_session_hint',
            'type' => 'custom_html',
            'value' => '<div class="alert alert-info">' . __('backend.session.validate_all_session_hint') . '</div>',
            'tab' => __('backend.session.inscriptions'),
        ]);
    }

    protected function setRatesTab()
    {

        CRUD::addField([
            'name' => 'tpv',         // nombre de la RELACIÓN (no tpv_id)
            'label' => 'TPV',
            'type' => 'relationship', // pinta un select2 con búsqueda
            'attribute' => 'name',        // columna visible en la lista
            'allows_null' => true,
            'tab' => __('backend.menu.rates'),
        ]);

        if ($this->crud->getCurrentOperation() === 'update') {
            CRUD::addField([
                'name' => 'rates',
                'label' => __('backend.rate.rates'),
                'type' => 'rates_vue',
                'tab' => __('backend.menu.rates'),
            ]);
        } else {       // en create: sólo mensaje
            CRUD::addField([
                'name' => 'rates_info',
                'type' => 'custom_html',
                'value' => '<p class="alert alert-info">' . __('backend.rate.msg_session_rate') . '</p>',
                'tab' => __('backend.menu.rates'),
            ]);
        }
    }

    private function setTicketTab()
    {
        CRUD::addField([
            'name' => 'session_color',
            'type' => 'color',
            'label' => __('backend.session.session_color'),
            'wrapperAttributes' => [
                'class' => 'form-group col-lg-6',
            ],
            'tab' => 'Ticket',
        ]);

        CRUD::addField([
            'name' => 'session_bg_color',
            'type' => 'color',
            'label' => __('backend.session.session_bg_color'),
            'wrapperAttributes' => [
                'class' => 'form-group col-lg-6',
            ],
            'tab' => 'Ticket',
        ]);

        CRUD::addField([
            'name' => 'custom_logo',
            'label' => __('backend.session.custom_logo'),
            'type' => 'image',
            'crop' => true,
            //'aspect_ratio' => 1.78,
            'upload' => true,
            'withFiles' => [
                'disk' => 'public',
                'uploader' => WebpImageUploader::class,
                'path' => 'uploads/' . get_current_brand()->code_name . '/session/' . ($this->crud->getCurrentEntry()?->id ?? 'temp'),
                'custom_name' => 'custom-logo',
                'resize' => [
                    'max' => 120
                ]
            ],
            'hint' => __('backend.session.minWidth'),
            'wrapperAttributes' => [
                'class' => 'form-group col-lg-12',
            ],
            'tab' => 'Ticket',
        ]);

        CRUD::addField([
            'name' => 'banner',
            'label' => __('backend.session.banner'),
            'type' => 'image',
            'crop' => true,
            //'aspect_ratio' => 1.78,
            'upload' => true,
            'withFiles' => [
                'disk' => 'public',
                'uploader' => WebpImageUploader::class,
                'path' => 'uploads/' . get_current_brand()->code_name . '/session/' . ($this->crud->getCurrentEntry()?->id ?? 'temp'),
                'custom_name' => 'banner',
                'resize' => [
                    'max' => 1200
                ]
            ],
            'hint' => __('backend.events.minWidth'),
            'wrapperAttributes' => [
                'class' => 'form-group col-lg-12',
            ],
            'tab' => 'Ticket'
        ]);
    }

    private function setCodesTab()
    {
        CRUD::addField([
            'name' => 'code_type',
            'label' => __('backend.session.code_type'),
            'type' => 'radio',
            'options' => [
                'null' => __('backend.session.code_type_null'),
                'session' => __('backend.session.code_type_session'),
                'census' => __('backend.session.code_type_census'),
                'user' => __('backend.session.code_type_user'),
            ],
            'allows_null' => false,
            'wrapperAttributes' => [
                'class' => 'form-group',
            ],
            'tab' => __('backend.rate.code'),
        ]);

        //comentado en el antiguo tambien

        /* $this->crud->addField([ // Table
            'name' => 'codes',
            'label' => '',
            'type' => 'table_custom',
            'entity_singular' => 'codes', // used on the "Add X" button
            'columns' => [
                'name' => 'Name',
                'code' =>  __('tincket/backend.rate.code')
            ],
            'tab' =>  __('tincket/backend.rate.code'),
        ]); */
    }
}
