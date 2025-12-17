<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Traits\AllowUsersTrait;
use App\Traits\CrudPermissionTrait;
use App\Scopes\BrandScope;
use App\Models\Inscription;
use App\Models\Event;

/**
 * Class InscriptionCrudController - Versión optimizada única
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class InscriptionCrudController extends CrudController
{
    use CrudPermissionTrait;
    use AllowUsersTrait;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(Inscription::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/inscription');
        CRUD::setEntityNameStrings(__('menu.inscription'), __('menu.inscriptions'));

        CRUD::denyAllAccess();
        if (auth()->check() && auth()->user()->hasPermissionTo('carts.index')) {
            $this->crud->allowAccess(['list', 'delete']);
        }
    }

    protected function setupListOperation()
    {
        // 1) Query base optimizada
        $this->setupOptimizedBaseQueryWithExists();

        // 2) Configurar columnas optimizadas
        $this->setupOptimizedColumns();

        // 3) Configurar filtros optimizados
        $this->setupOptimizedFilters();

        // 4) Configurar búsqueda optimizada
        $this->setupOptimizedSearch();

        // 5) Configurar paginación
        $this->crud->setDefaultPageLength(25);
    }

    /**
     * Query optimizada usando EXISTS en lugar de JOIN
     */
    private function setupOptimizedBaseQueryWithExists()
    {
        // Limpiar eager loads del modelo
        $this->crud->query->setEagerLoads([]);

        $locale = app()->getLocale() ?? 'ca';
        $brand = get_current_brand();

        // Query principal con subqueries para datos relacionados
        $this->crud->query
            ->select([
                'inscriptions.id',
                'inscriptions.session_id',
                'inscriptions.barcode',
                'inscriptions.code',
                'inscriptions.price_sold',
                'inscriptions.group_pack_id',
                'inscriptions.updated_at',
                'inscriptions.cart_id',
                'inscriptions.slot_id',

                // Subquery para nombre de sesión
                DB::raw("(
                    SELECT JSON_UNQUOTE(JSON_EXTRACT(sessions.name, '$.\"{$locale}\"'))
                    FROM sessions 
                    WHERE sessions.id = inscriptions.session_id
                    LIMIT 1
                ) as session_name"),

                // Subquery para starts_on
                DB::raw("(
                    SELECT sessions.starts_on
                    FROM sessions 
                    WHERE sessions.id = inscriptions.session_id
                    LIMIT 1
                ) as starts_on"),

                // Subquery para nombre de evento
                DB::raw("(
                    SELECT JSON_UNQUOTE(JSON_EXTRACT(events.name, '$.\"{$locale}\"'))
                    FROM events 
                    JOIN sessions ON sessions.event_id = events.id
                    WHERE sessions.id = inscriptions.session_id
                    LIMIT 1
                ) as event_name"),

                // Subquery para confirmation_code
                DB::raw("(
                    SELECT carts.confirmation_code
                    FROM carts 
                    WHERE carts.id = inscriptions.cart_id
                    LIMIT 1
                ) as confirmation_code"),

                // Subquery para client_id
                DB::raw("(
                    SELECT carts.client_id
                    FROM carts 
                    WHERE carts.id = inscriptions.cart_id
                    LIMIT 1
                ) as client_id"),

                // Subquery para gateway
                DB::raw("(
                    SELECT payments.gateway
                    FROM payments 
                    WHERE payments.cart_id = inscriptions.cart_id
                    AND payments.paid_at IS NOT NULL
                    AND payments.deleted_at IS NULL
                    LIMIT 1
                ) as gateway")
            ])
            // Filtrar por brand_id con índice
            ->where('inscriptions.brand_id', $brand->id)
            // Usar EXISTS en lugar de JOIN para inscripciones pagadas
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('payments')
                    ->whereRaw('payments.cart_id = inscriptions.cart_id')
                    ->whereNotNull('payments.paid_at')
                    ->whereNull('payments.deleted_at');
            })
            // Orden usando índices
            ->orderBy('inscriptions.updated_at', 'desc')
            ->orderBy('inscriptions.id', 'desc');
    }

    /**
     * Columnas optimizadas que usan los datos ya cargados
     */
    private function setupOptimizedColumns()
    {
        // column for list
        CRUD::addcolumns([
            [
                'label' => __('backend.inscription.event'),
                'name'  => 'event_name',
                'type'  => 'text',
                'limit' => 30,
                'orderable' => false,
                'visibleInTable'  => true,
                'visibleInModal'  => false,
                'searchLogic' => function ($query, $column, $searchTerm) {
                    $query->orWhereHas('session.event', function ($q) use ($column, $searchTerm) {
                        $q->where('name', 'like', '%' . $searchTerm . '%');
                    });
                },
                'wrapper' => [
                    'element' => 'span',
                    'title' => function ($crud, $column, $entry, $related_key) {
                        return $entry->event_name;
                    },
                ]
            ],
            [
                'label' => __('backend.inscription.event'),
                'key'   => 'event_fullname',
                'name'  => 'event_name',
                'type'  => 'text',
                'limit' => 255,
                'searchLogic' => false,
                'orderable' => false,
                'visibleInTable'  => false,
                'visibleInModal'  => true,
            ]
        ]);

        CRUD::addcolumns([
            [
                'name'  => 'session_display',
                'label' => __('backend.inscription.session'),
                'type'  => 'closure',
                'orderable' => false,
                'visibleInTable'  => true,
                'visibleInModal'  => false,
                'escaped' => false,
                'searchLogic' => function ($query, $column, $searchTerm) {
                    $query->orWhereHas('session', function ($q) use ($column, $searchTerm) {
                        $q->where('name', 'like', '%' . $searchTerm . '%');
                    });
                },
                'function' => function ($inscription) {
                    $sessionName = $inscription->session_name ?? '-';
                    $short = Str::limit($sessionName, 30, '...');
                    $fecha = $inscription->starts_on
                        ? \Carbon\Carbon::parse($inscription->starts_on)->format('d/m/Y H:i')
                        : '';
                    return '<span title="' . e($sessionName) . '">' . e($short) . '</span>' . ($fecha ? '<br><small class="text-muted">' . e($fecha) . '</small>' : '');
                },
            ],
            [
                'name'  => 'session_display',
                'key'   => 'session_display_full',
                'label' => __('backend.inscription.session'),
                'type'  => 'closure',
                'searchLogic' => false,
                'orderable' => false,
                'visibleInTable'  => false,
                'visibleInModal'  => true,
                'escaped' => false,
                'function' => function ($inscription) {
                    $sessionName = $inscription->session_name ?? '-';
                    $fecha = $inscription->starts_on
                        ? \Carbon\Carbon::parse($inscription->starts_on)->format('d/m/Y H:i')
                        : '';
                    return e($sessionName) . ($fecha ? '<br><small class="text-muted">' . e($fecha) . '</small>' : '');
                },
            ]
        ]);
        // Sesión con fecha
        CRUD::addColumn([
            'name'  => 'session_display',
            'label' => __('backend.inscription.session'),
            'type'  => 'closure',
            'searchLogic' => false,
            'orderable' => false,
            'escaped' => false,
            'function' => function ($inscription) {
                $sessionName = $inscription->session_name ?? '-';
                $short = Str::limit($sessionName, 10, '...');
                $fecha = $inscription->starts_on
                    ? \Carbon\Carbon::parse($inscription->starts_on)->format('d/m/Y H:i')
                    : '';
                return '<span title="' . e($sessionName) . '">' . e($short) . '</span>' . ($fecha ? '<br><small class="text-muted">' . e($fecha) . '</small>' : '');
            },
        ]);
        CRUD::addColumn([
            'name'  => 'session_display',
            'label' => __('backend.inscription.session'),
            'type'  => 'closure',
            'searchLogic' => false,
            'orderable' => false,
            'escaped' => false,
            'function' => function ($inscription) {
                $sessionName = $inscription->session_name ?? '-';
                $short = Str::limit($sessionName, 10, '...');
                $fecha = $inscription->starts_on
                    ? \Carbon\Carbon::parse($inscription->starts_on)->format('d/m/Y H:i')
                    : '';
                return '<span title="' . e($sessionName) . '">' . e($short) . '</span>' . ($fecha ? '<br><small class="text-muted">' . e($fecha) . '</small>' : '');
            },
        ]);

        CRUD::addColumn([
            'name'  => 'session_display',
            'label' => __('backend.inscription.session'),
            'type'  => 'closure',
            'searchLogic' => false,
            'orderable' => false,
            'escaped' => false,
            'function' => function ($inscription) {
                $sessionName = $inscription->session_name ?? '-';
                $short = Str::limit($sessionName, 10, '...');
                $fecha = $inscription->starts_on
                    ? \Carbon\Carbon::parse($inscription->starts_on)->format('d/m/Y H:i')
                    : '';
                return '<span title="' . e($sessionName) . '">' . e($short) . '</span>' . ($fecha ? '<br><small class="text-muted">' . e($fecha) . '</small>' : '');
            },
        ]);

        // Barcode
        CRUD::addColumn([
            'name' => 'barcode',
            'label' => __('backend.inscription.barcode'),
            'type' => 'text',
        ]);

        // Code
        CRUD::addColumn([
            'name' => 'code',
            'label' => __('backend.inscription.code'),
            'type' => 'text',
        ]);

        // Precio
        CRUD::addColumn([
            'name' => 'price_sold',
            'label' => __('backend.inscription.pricesold'),
            'type' => 'number',
            'decimals' => 2,
            'dec_point' => ',',
            'thousands_sep' => '.',
            'suffix' => ' €',
        ]);

        // Gateway
        CRUD::addColumn([
            'name' => 'gateway',
            'label' => __('backend.inscription.paymentplatform'),
            'type' => 'text',
        ]);

        // Fecha modificación
        CRUD::addColumn([
            'name'  => 'updated_at',
            'label' => __('backend.inscription.modifiedon'),
            'type'  => 'datetime',
            'format' => 'DD/MM/YYYY HH:mm'
        ]);

        // Cliente - link con ID ya cargado
        CRUD::addColumn([
            'label'   => __('backend.inscription.client'),
            'name'    => 'client_link',
            'type'    => 'closure',
            'escaped' => false,
            'searchLogic' => false,
            'function' => function ($inscription) {
                if ($inscription->client_id) {
                    $url = backpack_url("client/{$inscription->client_id}/show");
                    return '<a href="' . e($url) . '" target="_blank" class="btn btn-sm btn-link">
                            <i class="la la-user"></i> ' . e($inscription->client_id) . '
                           </a>';
                }
                return '<span class="text-muted">-</span>';
            },
        ]);

        // Origen
        CRUD::addColumn([
            'label'    => __('backend.inscription.origin'),
            'name'     => 'origin_display',
            'type'     => 'closure',
            'searchLogic' => false,
            'function' => fn($inscription) =>
            $inscription->group_pack_id ?
                '<span class="badge badge-info">Pack</span>' :
                '<span class="badge badge-secondary">Simple</span>',
            'escaped' => false,
        ]);

        // Código confirmación
        CRUD::addColumn([
            'label'   => __('backend.inscription.cardconfirmationcode'),
            'name'    => 'cart_link',
            'type'    => 'closure',
            'escaped' => false,
            'searchLogic' => false,
            'function' => function ($inscription) {
                if ($inscription->confirmation_code && $inscription->cart_id) {
                    $url = backpack_url("cart/{$inscription->cart_id}/show");
                    return '<a href="' . e($url) . '" target="_blank" class="btn btn-sm btn-link">
                            <i class="la la-shopping-cart"></i> ' . e($inscription->confirmation_code) . '
                           </a>';
                }
                return '<span class="text-muted">-</span>';
            },
        ]);
    }

    /**
     * Filtros optimizados usando EXISTS
     */
    private function setupOptimizedFilters()
    {
        // Filtro de Evento
        CRUD::addFilter(
            [
                'name' => 'event_id',
                'type' => 'select2',
                'label' => __('backend.inscription.event'),
            ],
            function () {
                return Event::query()
                    ->orderBy('name', 'asc')
                    ->pluck('name', 'id')
                    ->toArray();
            },
            function ($eventId) {
                $this->crud->query->whereExists(function ($query) use ($eventId) {
                    $query->select(DB::raw(1))
                        ->from('sessions')
                        ->whereRaw('sessions.id = inscriptions.session_id')
                        ->where('sessions.event_id', $eventId);
                });
            }
        );

        // Filtro de fecha
        CRUD::addFilter(
            [
                'type'  => 'date_range',
                'name'  => 'date_range',
                'label' => __('backend.inscription.date_range')
            ],
            false,
            function ($value) {
                $dates = json_decode($value);
                $this->crud->query->where('inscriptions.updated_at', '>=', $dates->from)
                    ->where('inscriptions.updated_at', '<=', $dates->to . ' 23:59:59');
            }
        );

        // Filtro de gateway usando subquery
        CRUD::addFilter(
            [
                'type' => 'dropdown',
                'name' => 'gateway',
                'label' => __('backend.inscription.paymentplatform')
            ],
            [
                'TicketOffice' => 'Taquilla',
                'Redsys Redirect' => 'TPV Online',
                'Free' => 'Gratuito'
            ],
            function ($value) {
                $this->crud->query->whereExists(function ($query) use ($value) {
                    $query->select(DB::raw(1))
                        ->from('payments')
                        ->whereRaw('payments.cart_id = inscriptions.cart_id')
                        ->where('payments.gateway', $value)
                        ->whereNotNull('payments.paid_at');
                });
            }
        );

        // Filtro de origen
        CRUD::addFilter(
            [
                'type' => 'dropdown',
                'name' => 'origin',
                'label' => __('backend.inscription.origin')
            ],
            [
                'simple' => 'Simple',
                'pack' => 'Pack'
            ],
            function ($value) {
                if ($value === 'simple') {
                    $this->crud->query->whereNull('inscriptions.group_pack_id');
                } else {
                    $this->crud->query->whereNotNull('inscriptions.group_pack_id');
                }
            }
        );
    }

    /**
     * Búsqueda optimizada
     */
    private function setupOptimizedSearch()
    {
        $this->crud->setOperationSetting('searchLogic', function ($query, $column, $searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                // Búsqueda directa en campos indexados
                $q->where('inscriptions.barcode', 'like', "%{$searchTerm}%")
                    ->orWhere('inscriptions.code', 'like', "%{$searchTerm}%");

                // Búsqueda en confirmation_code usando EXISTS
                if (strlen($searchTerm) >= 3) {
                    $q->orWhereExists(function ($sub) use ($searchTerm) {
                        $sub->select(DB::raw(1))
                            ->from('carts')
                            ->whereRaw('carts.id = inscriptions.cart_id')
                            ->where('carts.confirmation_code', 'like', "%{$searchTerm}%");
                    });
                }
            });
        });
    }

    public function generate($id)
    {
        $currentBrand = get_current_brand();

        $allowedBrandIds = collect([$currentBrand->id, $currentBrand->parent_id])
            ->merge($currentBrand->children->pluck('id'))
            ->unique()
            ->toArray();

        // Buscar la inscripción solo en las marcas permitidas
        $inscription = Inscription::withoutGlobalScope(BrandScope::class)
            ->whereHas('cart', function ($q) {
                $q->withoutGlobalScope(BrandScope::class)
                    ->whereNotNull('confirmation_code');
            })->with([
                'cart' => function ($q) {
                    $q->withoutGlobalScope(BrandScope::class);
                }
            ])
            ->where('id', $id)
            ->whereIn('brand_id', $allowedBrandIds)
            ->firstOrFail();

        // Preparar parámetros para la URL
        $pdfParams = [
            'inscription' => $inscription,
            'token' => $inscription->cart->token,
            'brand_code' => $inscription->cart->brand->code_name,
        ];

        // Determinar qué parámetros usar según el tipo de venta
        if ($inscription->cart->seller_type === 'App\Models\User' && !request()->has('web') || request()->get('ticket-office') == 1) {
            $params = brand_setting('base.inscription.ticket-office-params');
            $pdfParams['ticket-office'] = 1;
        } else {
            $params = brand_setting('base.inscription.ticket-web-params');
            $pdfParams['web'] = 1;
        }

        // 🔥 NUEVO: Generar URL interna
        $url = url(route('open.inscription.pdf', $pdfParams));

        try {
            // 🔥 NUEVO: Usar servicio local
            $pdfService = app(\App\Services\PdfGeneratorService::class);
            $pdf_content = $pdfService->generateFromUrl($url, $params);

            return response()->make($pdf_content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $inscription->barcode . '.pdf"'
            ]);
        } catch (\Exception $e) {
            \Log::error("Error generating inscription PDF", [
                'inscription_id' => $inscription->id,
                'error' => $e->getMessage()
            ]);

            abort(500, 'Error generando PDF: ' . $e->getMessage());
        }
    }

    public function updatePrice(Request $request)
    {
        $request->validate([
            'inscription_id' => 'required|exists:inscriptions,id',
            'rate_id' => 'required|exists:rates,id',
            'price' => 'required|numeric|min:0',
        ]);

        // ✅ Obtener brand actual y sus hijos permitidos
        $currentBrand = get_current_brand();
        $allowedBrandIds = array_merge(
            [$currentBrand->id],
            $currentBrand->children->pluck('id')->toArray()
        );

        // ✅ Buscar inscripción SIN BrandScope pero verificando que pertenece a brands permitidos
        $inscription = Inscription::withoutGlobalScope(\App\Scopes\BrandScope::class)
            ->whereIn('brand_id', $allowedBrandIds)
            ->findOrFail($request->inscription_id);

        $inscription->rate_id = $request->rate_id;
        $inscription->price = $request->price;
        $inscription->price_sold = $request->price;
        $inscription->save();

        return redirect()->back()->with('success', 'Inscripción actualizada correctamente.');
    }
}
