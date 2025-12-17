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
use App\Scopes\BrandScope;
use Illuminate\Support\Facades\DB;
use App\Uploaders\WebpImageUploader;
use App\Http\Requests\SessionRequest;
use App\Uploaders\PngImageUploader;
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
            'limit' => 255,
        ]);

        CRUD::addColumn([
            'label' => __('backend.session.event'),
            'type' => 'select',
            'name' => 'event_id',
            'entity' => 'event',
            'attribute' => 'name',
            'model' => Event::class,
            'limit' => 255,
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
            'limit' => 255,
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
        CRUD::enableExportButtons();
        CRUD::addColumn([
            'name' => 'visibility',
            'label' => '',
            'type' => 'closure',
            'function' => function ($entry) {
                if ($entry->visibility == 1) {
                    return '<span style="display:inline-block; width:12px; height:12px; border-radius:50%; background-color:green;" title="' . __('backend.session.visibility') . '"></span>';
                } else {
                    return '<span style="display:inline-block; width:12px; height:12px; border-radius:50%; background-color:red;" title="' . __('backend.session.no-visibility') . '"></span>';
                }
            },
            'escaped' => false,
        ]);

        if ($this->isSuperuser()) {
            CRUD::addColumn([
                'name' => 'liquidation',
                'label' => '<i class="la la-euro" title="' . __('backend.session.liquidation') . '"></i>',
                'type' => 'closure',
                'function' => function ($entry) {
                    if ($entry->liquidation) {
                        return '<i class="la la-check-circle text-success" style="font-size: 22px;" title="Liquidada"></i>';
                    } else {
                        return '<i class="la la-times-circle text-danger" style="font-size: 22px;" title="No liquidada"></i>';
                    }
                },
                'escaped' => false,
                'orderable' => true,
                'orderLogic' => function ($query, $column, $columnDirection) {
                    return $query->orderBy('liquidation', $columnDirection);
                },
                'wrapper' => [
                    'class' => 'text-center',
                ],
            ]);
        }

        CRUD::addcolumns([
            [
                'label' => __('backend.session.sessionname'),
                'name' => 'name',
                'type' => 'text',
                'limit' => 30,
                'visibleInTable' => true,
                'visibleInModal' => false,
                'searchLogic' => function ($query, $column, $searchTerm) {
                    $query->orWhere(DB::raw('lower(name)'), 'like', '%' . strtolower($searchTerm) . '%');
                },
                'wrapper' => [
                    'element' => 'span',
                    'title' => function ($crud, $column, $entry, $related_key) {
                        return $entry->name;
                    },
                ]
            ],
            [
                'name' => 'name',
                'key' => 'fullname',
                'label' => __('backend.session.sessionfullname'),
                'limit' => 255,
                'searchLogic' => false,
                'orderable' => false,
                'visibleInTable' => false,
                'visibleInModal' => true,
                'searchLogic' => function ($query, $column, $searchTerm) {
                    $query->orWhere(DB::raw('lower(name)'), 'like', '%' . strtolower($searchTerm) . '%');
                }
            ]
        ]);

        CRUD::addcolumns([
            [
                'name' => 'event_id',
                'label' => __('backend.session.event'),
                'type' => 'select',
                'attribute' => 'name',
                'limit' => 30,
                'visibleInTable' => true,
                'visibleInModal' => false,
                'model' => Event::class,
                'searchLogic' => function ($query, $column, $searchTerm) {
                    $query->orWhereHas('event', function ($q) use ($column, $searchTerm) {
                        $q->where(DB::raw('lower(name)'), 'like', '%' . strtolower($searchTerm) . '%');
                    });
                },
                'wrapper' => [
                    'element' => 'span',
                    'title' => function ($crud, $column, $entry, $related_key) {
                        return $entry->event->name;
                    },
                ]
            ],
            [
                'name' => 'event_id',
                'key' => 'event_fullname',
                'label' => __('backend.session.event_fullname'),
                'type' => 'select',
                'attribute' => 'name',
                'limit' => 255,
                'searchLogic' => false,
                'orderable' => false,
                'visibleInTable' => false,
                'visibleInModal' => true,
                'model' => Event::class,
            ]
        ]);

        CRUD::addColumn([
            'label' => __('backend.session.space'),
            'type' => 'select',
            'name' => 'space_id',
            'entity' => 'space',
            'attribute' => 'name',
            'model' => Space::class,
            'limit' => 15,
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

        // ✅ Orden correcto según parámetro show_incoming_sessions
        $showIncoming = request()->get('show_incoming_sessions', false);

        if ($showIncoming == 'true' || $showIncoming === true || $showIncoming === '1') {
            // Sesiones futuras ordenadas ASC (próximas primero)
            $this->crud->query
                ->where('starts_on', '>', now())
                ->orderBy('starts_on', 'ASC');
        } else {
            // Todas las sesiones ordenadas DESC (recientes primero)  
            $this->crud->query->orderBy('starts_on', 'DESC');
        }
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
        Widget::add()->type('script')->content(\Illuminate\Support\Facades\Vite::asset('resources/js/session-fill-max-places.js'));

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
        // 1. Obtener todos los slots del espacio
        $spaceSlots = $space->slots->keyBy('id');

        // 2. Obtener TODOS los SessionSlots de esta sesión
        $sessionSlots = SessionSlot::where('session_id', $session_id)->get()->keyBy('slot_id');

        // 3. Obtener autolocks
        $autolocks = SessionTempSlot::whereNull('expires_on')
            ->where('session_id', $session_id)
            ->get()
            ->keyBy('slot_id');

        // 4. Obtener vendidas
        $soldSlotsData = Inscription::withoutGlobalScope(BrandScope::class)->paid()
            ->where('session_id', $session_id)
            ->whereNotNull('slot_id')
            ->with([
                'cart' => function ($q) {
                    $q->withoutGlobalScope(BrandScope::class);
                },
            ])
            ->get()
            ->mapWithKeys(function ($inscription) {
                return [$inscription->slot_id => $inscription];
            });

        // 5. Crear el mapa con prioridad correcta
        $slots_map = $spaceSlots->map(function ($slot) use ($sessionSlots, $autolocks, $soldSlotsData) {
            $slotId = $slot->id;

            // PRIORIDAD 1: Vendida (siempre gana)
            if ($soldSlotsData->has($slotId)) {
                $inscription = $soldSlotsData[$slotId];
                $result = [
                    'id' => $slotId,
                    'name' => $slot->name,
                    'status_id' => 2,
                    'comment' => null,
                    'x' => $slot->x,
                    'y' => $slot->y,
                    'zone_id' => $slot->zone_id,
                    'type' => 'Sold',
                    'cart_id' => $inscription->cart_id,
                    'confirmation_code' => $inscription->cart?->confirmation_code,
                ];

                return $result;
            }

            // 🔥 PRIORIDAD 2: Autolock
            if ($autolocks->has($slotId)) {
                $autolock = $autolocks[$slotId];
                $result = [
                    'id' => $slotId,
                    'name' => $slot->name,
                    'status_id' => $autolock->status_id,
                    'comment' => trans('backend.session.autolock_type'),
                    'x' => $slot->x,
                    'y' => $slot->y,
                    'zone_id' => $slot->zone_id,
                    'type' => 'Autolock',
                ];

                return $result;
            }

            // PRIORIDAD 3: SessionSlot (INCLUSO SI ES NULL)
            if ($sessionSlots->has($slotId)) {
                $sessionSlot = $sessionSlots[$slotId];
                $result = [
                    'id' => $slotId,
                    'name' => $slot->name,
                    'status_id' => $sessionSlot->status_id, // ← Puede ser null
                    'comment' => $sessionSlot->comment,
                    'x' => $slot->x,
                    'y' => $slot->y,
                    'zone_id' => $slot->zone_id,
                    'type' => 'SessionSlot',
                ];

                return $result;
            }

            //  PRIORIDAD 4: Space (solo si no hay SessionSlot)
            $result = [
                'id' => $slotId,
                'name' => $slot->name,
                'status_id' => $slot->status_id,
                'comment' => $slot->comment,
                'x' => $slot->x,
                'y' => $slot->y,
                'zone_id' => $slot->zone_id,
                'type' => 'Space',
            ];

            return $result;
        });


        $this->data['slots_map'] = $slots_map->values()->all();
        $this->data['zones_map'] = $space->zones->map(function ($zone) {
            return [
                'id' => $zone->id,
                'name' => $zone->name,
                'color' => $zone->color,
            ];
        })->values()->all();

        \View::share('slots_map', $this->data['slots_map']);
        \View::share('zones_map', $this->data['zones_map']);

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

        if ($this->isSuperuser()) {
            CRUD::addFilter([
                'name' => 'liquidation',
                'type' => 'dropdown',
                'label' => __('backend.session.liquidation')
            ], [
                1 => 'Liquidadas',
                0 => 'No liquidadas',
            ], function ($value) {
                $this->crud->addClause('where', 'liquidation', $value);
            });
        }
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

        CRUD::addField([
            'name' => 'starts_on',
            'label' => __('backend.session.startson'),
            'type' => 'datetime_picker',
            'datetime_picker_options' => [
                'format' => 'DD/MM/YYYY HH:mm',
                'language' => app()->getLocale(),
                'stepping' => 5, // incrementos de 5 min
                'sideBySide' => true, // calendario y reloj lado a lado
            ],
            'allows_null' => false,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3',
            ],
            'tab' => 'Basic',
        ]);

        CRUD::addField([
            'name' => 'ends_on',
            'label' => __('backend.session.endson'),
            'type' => 'datetime_picker',
            'datetime_picker_options' => [
                'format' => 'DD/MM/YYYY HH:mm',
                'language' => app()->getLocale(),
                'stepping' => 5,
                'sideBySide' => true,
            ],
            'allows_null' => false,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3',
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
            'hint' => __('backend.session.hint_no_space') . '<a href="' . route('space.create') . '"> Crear aquí</a>',
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
            'type' => 'session_private',
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
            ////'extraPlugins' => ['oembed'],
            'wrapperAttributes' => [],
            'tab' => 'Basic',
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => __('backend.session.description'),
            'type' => 'ckeditor',
            ////'extraPlugins' => ['oembed'],
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
        CRUD::addField([
            'name' => 'inscription_starts_on',
            'label' => __('backend.session.inscriptionstarts'),
            'type' => 'datetime_picker',
            'datetime_picker_options' => [
                'format' => 'DD/MM/YYYY HH:mm',
                'language' => app()->getLocale(),
                'stepping' => 5,
                'sideBySide' => true,
            ],
            'allows_null' => false,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3',
            ],
            'tab' => __('backend.session.inscriptions'),
        ]);

        CRUD::addField([
            'name' => 'inscription_ends_on',
            'label' => __('backend.session.inscriptionends'),
            'type' => 'datetime_picker',
            'datetime_picker_options' => [
                'format' => 'DD/MM/YYYY HH:mm',
                'language' => app()->getLocale(),
                'stepping' => 5,
                'sideBySide' => true,
            ],
            'allows_null' => false,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-3',
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
            'default' => null,
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
            'tab' => __('menu.rates'),
        ]);

        if ($this->crud->getCurrentOperation() === 'update') {
            CRUD::addField([
                'name' => 'rates',
                'label' => __('backend.rate.rates'),
                'type' => 'rates_vue',
                'tab' => __('menu.rates'),
            ]);
        } else {       // en create: sólo mensaje
            CRUD::addField([
                'name' => 'rates_info',
                'type' => 'custom_html',
                'value' => '<p class="alert alert-info">' . __('backend.rate.msg_session_rate') . '</p>',
                'tab' => __('menu.rates'),
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
            'upload' => true,
            'withFiles' => [
                'disk' => 'public',
                'uploader' => PngImageUploader::class,
                'path' => 'uploads/' . get_current_brand()->code_name . '/session/' . ($this->crud->getCurrentEntry()?->id ?? 'temp'),
                'custom_name' => 'custom-logo',
                'resize' => [
                    'max' => 300
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
            'upload' => true,
            'withFiles' => [
                'disk' => 'public',
                'uploader' => PngImageUploader::class,
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
            'default' => 'null',
            'allows_null' => false,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
            'tab' => __('backend.rate.code'),
        ]);

        CRUD::addField([
            'name' => 'limit_per_user',
            'label' => __('backend.session.limit_per_user'),
            'type' => 'switch',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => __('backend.rate.code'),
        ]);

        CRUD::addField([
            'name' => 'max_per_user',
            'label' => __('backend.session.max_per_user'),
            'type' => 'number',
            'attributes' => [
                'min' => 1,
                'max' => 100,
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6 max-per-user-field',
            ],
            'tab' => __('backend.rate.code'),
        ]);

        CRUD::addField([
            'name' => 'max_per_user_info',
            'type' => 'custom_html',
            'value' => '<div class="alert alert-info mb-0">
        <strong><i class="la la-info-circle"></i> ' . __('backend.session.max_per_user_info_title') . '</strong><br>
        ' . __('backend.session.max_per_user_info') . '
    </div>',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
            'tab' => __('backend.rate.code'),
        ]);

        CRUD::addField([
            'name' => 'limit_per_user_script',
            'type' => 'custom_html',
            'value' => '<script>
        document.addEventListener("DOMContentLoaded", function() {
            function toggleMaxPerUser() {
                const input = document.querySelector("input[name=\"limit_per_user\"]");
                const field = document.querySelector(".max-per-user-field"); // Solo el input numérico
                
                if (input && field) {
                    if (input.value === "1") {
                        field.style.display = "block";
                    } else {
                        field.style.display = "none";
                    }
                }
            }
            
            setTimeout(toggleMaxPerUser, 500);
            
            const input = document.querySelector("input[name=\"limit_per_user\"]");
            if (input) {
                const observer = new MutationObserver(toggleMaxPerUser);
                observer.observe(input, { attributes: true, attributeFilter: ["value"] });
                input.addEventListener("change", toggleMaxPerUser);
            }
        });
    </script>',
            'tab' => __('backend.rate.code'),
        ]);
    }
}
