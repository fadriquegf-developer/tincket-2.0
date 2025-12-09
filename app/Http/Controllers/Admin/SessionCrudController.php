<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Tpv;
use App\Models\Rate;
use App\Models\Zone;
use App\Models\Event;
use App\Models\Space;
use App\Models\Session;
use App\Models\SessionCode;
use App\Models\SessionSlot;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Traits\SessionCrudUi;
use App\Models\AssignatedRate;
use App\Traits\AllowUsersTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\RedisSlotsService;
use Illuminate\Http\JsonResponse;
use App\Imports\SessionCodeImport;
use App\Observers\SessionObserver;
use Illuminate\Support\Facades\DB;
use Prologue\Alerts\Facades\Alert;
use App\Traits\CrudPermissionTrait;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\SessionRequest;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use App\Http\Requests\StoreMultiSessionRequest;
use App\Jobs\UpdateSessionSlotCache;
use App\Models\Inscription;
use App\Repositories\SessionRepository;
use App\Scopes\BrandScope;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\Cache;

/**
 * Class SessionCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SessionCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\Pro\Http\Controllers\Operations\DropzoneOperation;
    use SessionCrudUi;
    use AllowUsersTrait;
    use CrudPermissionTrait;

    protected $session_id;

    public function setup()
    {
        CRUD::setModel(Session::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/session');
        CRUD::setEntityNameStrings(__('menu.session'), __('menu.sessions'));

        $this->setAccessUsingPermissions();

        if ($this->isSuperuser()) {
            CRUD::addButtonFromView('line', 'regenerate', 'regenerate', 'end');
            CRUD::addButtonFromView('line', 'list_pdf_errors', 'list_pdf_errors', 'end');
            CRUD::addButtonFromView('line', 'liquidation', 'liquidation', 'end');
        }

        CRUD::addButtonFromView('line', 'inscriptions', 'inscriptions_link', 'end');
        //CRUD::addButtonFromView('line', 'import_codes', 'import_session_codes', 'end');                               //no funciona el modal
        CRUD::addButtonFromModelFunction('line', 'show_session', 'getShowSessionButton', 'end');
        CRUD::addButtonFromModelFunction('line', 'clone_session', 'getCloneSessionButton', 'end');
    }

    public function update(SessionRequest $request)
    {
        $session = $this->crud->getCurrentEntry();
        $oldImages = $session->images ?? [];
        $needsCacheRegeneration = false;

        //Session::unsetEventDispatcher();
        $response = $this->traitUpdate();
        //Session::setEventDispatcher(app('events'));

        $session = $this->crud->getCurrentEntry();
        SessionObserver::processImages($session);

        $session->refresh();
        $newImages = $session->images ?? [];

        $toDelete = array_diff($oldImages, $newImages);
        foreach ($toDelete as $oldPath) {
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        if ((bool) $request->input('is_rates_table_dirty', false)) {
            $this->updatePrices($request);
            $needsCacheRegeneration = true;
        }

        if ($request->filled('slot_labels')) {
            $this->updateSlots($session);
            $needsCacheRegeneration = true;
        }

        if ($needsCacheRegeneration && $session->is_numbered) {
            UpdateSessionSlotCache::dispatch($session);
        }

        return $response;
    }

    protected function updateSlots(Session $session): void
    {
        $locked_slots = collect(json_decode(request()->input('slot_labels')));
        $redisService = new RedisSlotsService($session);

        // Eliminar SessionTempSlots caducados con diferencias de estado
        $session_temp_slots = \App\Models\SessionTempSlot::notExpired()
            ->whereSessionId($session->id)
            ->get();

        $session_temp_slots->each(function ($slot) use ($locked_slots) {
            $new_slot = $locked_slots->where('id', $slot->slot_id);
            if ($new_slot->count() > 0) {
                if ($slot->expires_on === null && $new_slot->first()->status_id !== $slot->status_id) {
                    $slot->delete();
                }
            }
        });

        foreach ($locked_slots as $slot_data) {
            $slotId = $slot_data->id;
            $statusId = $slot_data->status_id ?? null;
            $comment = $slot_data->comment ?? null;

            // Verificar si está vendida
            $isSold = Inscription::paid()
                ->where('session_id', $session->id)
                ->where('slot_id', $slotId)
                ->exists();

            if ($isSold) {
                SessionSlot::updateOrCreate(
                    ['session_id' => $session->id, 'slot_id' => $slotId],
                    ['status_id' => 2, 'comment' => null]
                );
                continue;
            }

            SessionSlot::updateOrCreate(
                ['session_id' => $session->id, 'slot_id' => $slotId],
                ['status_id' => $statusId, 'comment' => $comment]
            );

            // Actualizar Redis
            if ($statusId === null) {
                $redisService->freeSlot($slotId);
            } else {
                $redisService->updateSlotState($slotId, [
                    'is_locked' => true,
                    'lock_reason' => $statusId,
                    'comment' => $comment
                ]);
            }
        }
    }

    protected function updatePrices(Request $request)
    {
        $this->session_id = is_a($this->crud->entry, Session::class) ? $this->crud->entry->id : null;

        if (!$this->session_id) {
            throw new \Exception("Session ID not found");
        }

        DB::transaction(function () use ($request) {
            // Eliminar tarifas anteriores
            AssignatedRate::where('session_id', $this->session_id)->delete();

            // Procesar según tipo de sesión
            if ($this->crud->entry->is_numbered) {
                $this->updateNumberedSessionPrices($request);
            } else {
                $this->updateNonNumberedSessionPrices($request);
            }

            // IMPORTANTE: Invalidar toda la cache después de actualizar precios
            $this->invalidatePriceCache();
        });
    }

    /**
     * Invalidar cache después de actualizar precios
     */
    protected function invalidatePriceCache(): void
    {
        try {
            $session = $this->crud->entry;

            // Invalidar cache de Laravel
            Cache::tags(["session:{$session->id}"])->flush();

            // Cache específicos de tarifas
            $brandPrefix = $session->brand_id ? "b{$session->brand_id}" : 'default';

            // Limpiar cache de tarifas por zona
            if ($session->is_numbered && $session->space) {
                foreach ($session->space->zones as $zone) {
                    Cache::forget("{$brandPrefix}:rates:s{$session->id}:z{$zone->id}");
                }
            }

            // Limpiar cache de disponibilidad
            Cache::forget("{$brandPrefix}:free:s{$session->id}");
            Cache::forget("{$brandPrefix}:available_web:s{$session->id}");
            Cache::forget("{$brandPrefix}:blocked:s{$session->id}");
            Cache::forget("session_{$session->id}_public_rates_formatted");
            Cache::forget("session_{$session->id}_general_rate");

            // Invalidar Redis si es numerada
            if ($session->is_numbered) {
                $redisService = new RedisSlotsService($session);
                $redisService->clearAllCache();

                // Disparar job para regenerar cache
                UpdateSessionSlotCache::dispatch($session);
            }

            // Invalidar cache del repository
            app(SessionRepository::class)->invalidateRateCache($this->crud->entry);
        } catch (\Exception $e) {
            Log::error("SessionCrudController: Error invalidating price cache", [
                'session_id' => $this->session_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function updateNumberedSessionPrices(Request $request)
    {
        $rawRates = $request->get('rates')
            ? json_decode($request->get('rates'), true)
            : [];

        $zones = Zone::where('space_id', $this->crud->entry->space_id)->get();

        foreach ($zones as $zone) {
            $filtered = array_filter($rawRates, function ($r) use ($zone) {
                return isset($r['assignated_rate_id'])
                    && $r['assignated_rate_id'] == $zone->id;
            });

            foreach ($filtered as $raw) {
                if (empty($raw['rate']['id'])) {
                    continue;
                }

                $data = [
                    'session_id' => $this->session_id,
                    'rate_id' => $raw['rate']['id'],
                    'price' => $raw['price'] ?? 0,
                    'max_on_sale' => $raw['max_on_sale'] ?? 0,
                    'max_per_order' => $raw['max_per_order'] ?? 0,
                    'available_since' => $raw['available_since'] ?? null,
                    'available_until' => $raw['available_until'] ?? null,
                    'is_public' => (bool) ($raw['is_public'] ?? false),
                    'is_private' => (bool) ($raw['is_private'] ?? false),
                    'max_per_code' => $raw['max_per_code'] ?? null,
                    'validator_class' => $raw['validator_class'] ?? null,
                    'assignated_rate_type' => Zone::class,
                    'assignated_rate_id' => $zone->id,
                ];

                AssignatedRate::create($data);
            }
        }
    }

    protected function updateNonNumberedSessionPrices(Request $request)
    {
        $rawRates = $request->get('rates')
            ? json_decode($request->get('rates'), true)
            : [];

        foreach ($rawRates as $raw) {
            if (empty($raw['rate']['id'])) {
                continue;
            }

            $data = [
                'session_id' => $this->session_id,
                'rate_id' => $raw['rate']['id'],
                'price' => $raw['price'] ?? 0,
                'max_on_sale' => $raw['max_on_sale'] ?? 0,
                'max_per_order' => $raw['max_per_order'] ?? 0,
                'available_since' => $raw['available_since'] ?? null,
                'available_until' => $raw['available_until'] ?? null,
                'is_public' => (bool) ($raw['is_public'] ?? false),
                'is_private' => (bool) ($raw['is_private'] ?? false),
                'max_per_code' => $raw['max_per_code'] ?? null,
                'validator_class' => $raw['validator_class'] ?? null,
                'assignated_rate_type' => Session::class,
                'assignated_rate_id' => $this->session_id,
            ];

            AssignatedRate::create($data);
        }
    }


    protected function updatePricesPerZone(array $rates, Zone $zone)
    {
        $rates = array_filter($rates, function ($rate) use ($zone) {
            return ($rate->assignated_rate_id ?? null) === $zone->id;
        });

        foreach ($rates as $rate) {
            $this->prepareRateObject($rate);

            if ($rate->rate_id) {
                $rate->session_id = $this->session_id;
                $zone->rates($this->session_id)->attach(
                    $rate->rate_id,
                    array_intersect_key(get_object_vars($rate), array_flip(
                        [
                            'session_id',
                            'price',
                            'max_on_sale',
                            'max_per_order',
                            'available_since',
                            'available_until',
                            'is_public',
                            'is_private',
                            'max_per_code',
                            'validator_class'
                        ]
                    ))
                );
            }
        }
    }

    protected function prepareRateObject(array $rate): array
    {
        // 1) Añade el session_id
        $rate['session_id'] = $this->session_id;

        // 2) Extrae el rate_id
        $rate['rate_id'] = $rate['rate']['id'] ?? null;

        // 3) Limpia validator_class
        $rate['validator_class'] = null;

        // 4) Ajusta available_since/until
        if (isset($rate['available_since']) && ($rate['is_public'] ?? false)) {
            $rate['available_since'] = $rate['available_since'] === '1970-01-01T01:00:00+01:00'
                ? null
                : date("Y-m-d H:i:s", strtotime($rate['available_since']));
            $rate['available_until'] = $rate['available_until'] === '1970-01-01T01:00:00+01:00'
                ? null
                : date("Y-m-d H:i:s", strtotime($rate['available_until']));
        } else {
            $rate['available_since'] = null;
            $rate['available_until'] = null;
        }

        // 5) Si hay validator en la tarifa original, lo guardamos como JSON
        if (!empty($rate['rate']['validator_class'])) {
            $rate['validator_class'] = json_encode([
                'class' => $rate['rate']['validator_class'],
                'attr' => $rate['rate']['validator_class_attr'] ?? [],
            ]);
        }

        return $rate;
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
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
            'tab' => 'Basic',
        ]);
    }

    public function inscriptions($id)
    {
        /* 1) Sesión + relaciones */
        $session = Session::with(['event', 'space'])->findOrFail($id);

        /* 2) Inscripciones SIN BrandScope */
        $inscriptions = Inscription::withoutGlobalScope(BrandScope::class)
            ->where('session_id', $session->id)
            ->whereHas('cart', function ($q) {
                $q->withoutGlobalScope(BrandScope::class)
                    ->whereNotNull('confirmation_code');
            })
            ->with([
                'cart' => function ($q) {
                    $q->withoutGlobalScope(BrandScope::class)
                        ->with([
                            'client' => fn($c) => $c->withoutGlobalScope(BrandScope::class),
                            'payments' => fn($p) => $p->withoutGlobalScope(BrandScope::class)->whereNotNull('payments.paid_at')
                        ]);
                },
                'rate' => fn($q) => $q->withoutGlobalScope(BrandScope::class),
                'slot'
            ])
            ->get();

        /* 3) Contadores */
        $validated = $inscriptions->whereNotNull('checked_at')->count();
        $validatedIn = $session->getValidatedCount() - $session->getValidatedOutCount();
        $validatedOut = $session->getValidatedOutCount();

        $sellWebEntries = $inscriptions->filter(function ($i) {
            $gateway = $i->cart?->payments?->first()?->gateway;
            return in_array($gateway, ['Sermepa', 'SermepaSoapService', 'RedsysSoapService', 'Redsys Redirect', 'Free']);
        });

        $sellOfficeEntries = $inscriptions->filter(function ($i) {
            return $i->cart?->payments?->first()?->gateway === 'TicketOffice';
        });

        $stats = [
            'total' => $inscriptions->count(),
            'validated' => $validated,
            'validated_in' => $validatedIn,
            'validated_out' => $validatedOut,
            'web_entries' => $sellWebEntries->count(),
            'web_amount' => round($sellWebEntries->sum('price_sold'), 2),
            'office_entries' => $sellOfficeEntries->count(),
            'office_amount' => round($sellOfficeEntries->sum('price_sold'), 2),
            'total_amount' => round($inscriptions->sum('price_sold'), 2),
        ];

        /* 4) DNIs duplicados */
        $dniRepeated = $inscriptions
            ->pluck('metadata')
            ->filter()
            ->map(fn($m) => $m['dni'] ?? null)
            ->filter()
            ->duplicates();

        $duplicates = $inscriptions->filter(function ($i) use ($dniRepeated) {
            $dni = $i->metadata['dni'] ?? null;
            return $dni && $dniRepeated->contains($dni);
        });

        return view(
            'core.session.inscriptions',
            compact('session', 'inscriptions', 'stats', 'duplicates')
        );
    }


    /**
     * Exporta las inscripciones de una sesión a Excel
     * INCLUYE COLUMNAS DINÁMICAS DE METADATA DE FORMULARIOS
     */
    public function exportExcel($sessionId)
    {
        \Log::info("=== INICIO EXPORT EXCEL - Session ID: {$sessionId} ===");

        // 1) Cargo la sesión CON allRates (AssignatedRates) y sus rates con forms
        $session = Session::with([
            'allRates' => function ($q) {
                $q->with(['rate' => function ($r) {
                    $r->with(['form' => function ($f) {
                        $f->with(['form_fields' => function ($ff) {
                            $ff->orderBy('form_form_field.order');
                        }]);
                    }]);
                }]);
            }
        ])->findOrFail($sessionId);
        \Log::info("Session cargada: {$session->name}");
        \Log::info("Session->allRates después del with(): " . ($session->allRates === null ? 'NULL' : 'EXISTS'));
        \Log::info("Relaciones cargadas: " . json_encode($session->getRelations()));

        // 2) Cargo las inscripciones SIN BrandScope
        $inscriptions = Inscription::withoutGlobalScope(BrandScope::class)
            ->without(['session', 'rate', 'slot'])
            ->where('session_id', $session->id)
            ->whereHas('cart', function ($q) {
                $q->withoutGlobalScope(BrandScope::class)
                    ->whereNotNull('confirmation_code');
            })
            ->with([
                'cart' => function ($q) {
                    $q->withoutGlobalScope(BrandScope::class)
                        ->with([
                            'client' => fn($c) => $c->withoutGlobalScope(BrandScope::class),
                            'payments' => fn($p) => $p->withoutGlobalScope(BrandScope::class)
                                ->whereNotNull('payments.paid_at')
                        ]);
                },
                'rate' => fn($q) => $q->withoutGlobalScope(BrandScope::class)
                    ->with(['form' => function ($f) {
                        $f->with(['form_fields' => function ($ff) {
                            $ff->orderBy('form_form_field.order');
                        }]);
                    }]),
                'slot'
            ])
            ->get();

        \Log::info("Total inscripciones cargadas: " . $inscriptions->count());

        // LOG: Mostrar metadata de cada inscripción
        foreach ($inscriptions->take(5) as $idx => $i) {
            $meta = is_array($i->metadata) ? $i->metadata : json_decode($i->metadata, true) ?? [];
            \Log::info("Inscripción #{$idx} - ID: {$i->id}");
            \Log::info("  - Rate ID: " . ($i->rate_id ?? 'NULL'));
            \Log::info("  - Rate Name: " . ($i->rate->name ?? 'NULL'));
            \Log::info("  - Rate Form ID: " . ($i->rate->form_id ?? 'NULL'));
            \Log::info("  - Metadata RAW: " . json_encode($i->metadata));
            \Log::info("  - Metadata DECODED: " . json_encode($meta));
            \Log::info("  - Metadata KEYS: " . implode(', ', array_keys($meta)));
        }

        // 3) Detectar todos los form_fields que tienen respuestas en metadata
        // Passem allRates directament per evitar problemes amb relacions
        $formFieldsUsed = $this->detectUsedFormFields($inscriptions, $session->allRates);

        \Log::info("Form fields detectados: " . count($formFieldsUsed));
        \Log::info("Form fields used: " . json_encode($formFieldsUsed));

        // 4) Preparo los datos con columnas dinámicas
        $rows = $inscriptions->map(function ($i) use ($formFieldsUsed) {
            $client = $i->cart?->client;
            $slot = $i->slot;
            $meta = is_array($i->metadata) ?
                $i->metadata : json_decode($i->metadata, true) ?? [];

            $payment = $i->cart?->payments?->first();

            // Columnas estándar (las que ya existían)
            $row = [
                'Nombre' => $client?->name ?? '',
                'Apellidos' => $client?->surname ?? '',
                'Email' => $client?->email ?? '',
                'Teléfono' => $client?->phone ?? '',
                'Confirmación' => $i->cart?->confirmation_code ?? '',
                'Plataforma' => $payment?->gateway ?? '',
                'Tarifa' => $i->rate?->name ?? '',
                'Precio' => number_format($i->price_sold, 2),
                'Posición en mapa' => $slot?->name ?? 'n/a',
                'Código de barras' => $i->barcode ?? '',
                'Validado' => $i->checked_at ? 'Sí' : 'No',
                'DNI' => $meta['dni'] ?? '',
                'Creado' => $i->cart?->created_at?->format('d/m/Y H:i') ?? '',
            ];

            // ✨ NUEVO: Añadir columnas dinámicas de form_fields
            foreach ($formFieldsUsed as $fieldName => $fieldLabel) {
                $row[$fieldLabel] = $this->formatMetadataValue($meta[$fieldName] ?? '');
            }

            return $row;
        })->toArray();

        // 5) Clase anónima para Excel con columnas dinámicas
        $export = new class(collect($rows)) implements FromCollection, WithHeadings, WithEvents {
            private $collection;

            public function __construct($collection)
            {
                $this->collection = $collection;
            }

            public function collection()
            {
                return $this->collection;
            }

            public function headings(): array
            {
                return $this->collection->first() ? array_keys($this->collection->first()) : [];
            }

            public function registerEvents(): array
            {
                return [
                    AfterSheet::class => function (AfterSheet $event) {
                        $sheet = $event->sheet->getDelegate();
                        $rowCount = $this->collection->count() + 1;

                        // Estilo zebra
                        for ($row = 2; $row <= $rowCount; $row++) {
                            if ($row % 2 === 0) {
                                $sheet
                                    ->getStyle("A{$row}:" . $sheet->getHighestColumn() . "{$row}")
                                    ->getFill()
                                    ->setFillType(Fill::FILL_SOLID)
                                    ->getStartColor()
                                    ->setARGB('FFF0F0F0');
                            }
                        }
                    },
                ];
            }
        };

        // 6) Descarga
        return Excel::download($export, "inscripciones-{$sessionId}.xlsx");
    }

    /**
     * Genera un PDF imprimible de las inscripciones
     * INCLUYE METADATA DE FORMULARIOS
     */
    public function printInscr($sessionId)
    {
        // ✅ Aumentar límite de memoria temporalmente
        ini_set('memory_limit', '256M');

        // 1) Cargar sesión CON allRates (AssignatedRates) y sus rates con forms
        $session = Session::select('id', 'name', 'event_id', 'starts_on')
            ->with([
                'event:id,name',
                'allRates' => function ($q) {
                    $q->with(['rate' => function ($r) {
                        $r->with(['form' => function ($f) {
                            $f->with(['form_fields' => function ($ff) {
                                $ff->orderBy('form_form_field.order');
                            }]);
                        }]);
                    }]);
                }
            ])
            ->findOrFail($sessionId);

        // 2) ✅ QUERY OPTIMIZADA con form_fields
        $inscriptions = Inscription::withoutGlobalScope(BrandScope::class)
            ->select([
                'inscriptions.id',
                'inscriptions.cart_id',
                'inscriptions.rate_id',
                'inscriptions.slot_id',
                'inscriptions.barcode',
                'inscriptions.checked_at',
                'inscriptions.metadata'
            ])
            ->where('inscriptions.session_id', $session->id)
            ->whereHas('cart', function ($q) {
                $q->withoutGlobalScope(BrandScope::class)
                    ->whereNotNull('confirmation_code');
            })
            ->with([
                'cart' => function ($q) {
                    $q->withoutGlobalScope(BrandScope::class)
                        ->with([
                            'client' => fn($c) => $c->withoutGlobalScope(BrandScope::class),
                            'confirmedPayment' => fn($p) => $p->withoutGlobalScope(BrandScope::class)
                                ->whereNotNull('payments.paid_at')
                        ]);
                },
                'rate' => fn($q) => $q->withoutGlobalScope(BrandScope::class)
                    ->with(['form' => function ($f) {
                        $f->with(['form_fields' => function ($ff) {
                            $ff->orderBy('form_form_field.order');
                        }]);
                    }]),
                'slot',
                'slot.zone'
            ])
            ->orderBy('inscriptions.created_at')
            ->get();

        // ✨ NUEVO: Detectar form_fields usados para el PDF (passem allRates directament)
        $formFieldsUsed = $this->detectUsedFormFields($inscriptions, $session->allRates);

        // 3) Generar PDF con configuración optimizada
        $pdf = Pdf::loadView('core.session.print-inscriptions', compact('session', 'inscriptions', 'formFieldsUsed'))
            ->setPaper('a4', 'landscape')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('dpi', 96);

        return $pdf->stream("inscripciones-{$sessionId}.pdf");
    }

    /**
     * ✨ NUEVA FUNCIÓN: Detecta qué form_fields tienen respuestas en metadata
     * 
     * @param Collection $inscriptions
     * @param Collection $allRates - Col·lecció de AssignatedRates amb rates carregats
     * @return array ['field_name' => 'Field Label', ...]
     */
    private function detectUsedFormFields($inscriptions, $allRates)
    {
        \Log::info("=== INICIO detectUsedFormFields ===");

        $formFieldsUsed = [];
        $locale = app()->getLocale();
        \Log::info("Locale actual: {$locale}");

        // Obtener todos los form_fields de los allRates (AssignatedRates)
        $allFormFields = collect();

        \Log::info("Total AssignatedRates recibidos: " . $allRates->count());

        foreach ($allRates as $assignatedRate) {
            $rate = $assignatedRate->rate; // El modelo Rate real

            if (!$rate) {
                \Log::info("AssignatedRate ID {$assignatedRate->id} NO tiene rate asociado");
                continue;
            }

            \Log::info("Rate ID {$rate->id} - Name: {$rate->name} - Form ID: " . ($rate->form_id ?? 'NULL'));

            if ($rate->form_id && $rate->form) {
                \Log::info("  Form encontrado - ID: {$rate->form->id} - Name: {$rate->form->name}");
                \Log::info("  Form tiene " . $rate->form->form_fields->count() . " form_fields");

                foreach ($rate->form->form_fields as $field) {
                    \Log::info("    Field ID: {$field->id} - Name: {$field->name} - Type: {$field->type}");
                    \Log::info("    Field Label RAW: " . json_encode($field->label));

                    // Evitar duplicados por ID
                    if (!$allFormFields->contains('id', $field->id)) {
                        $allFormFields->push($field);
                    }
                }
            } else {
                \Log::info("  Rate NO tiene form asignado");
            }
        }

        \Log::info("Total form_fields únicos encontrados: " . $allFormFields->count());

        // Iterar sobre todas las inscripciones para detectar qué campos tienen respuestas
        $inscriptionsChecked = 0;
        foreach ($inscriptions as $inscription) {
            $meta = is_array($inscription->metadata) ?
                $inscription->metadata :
                json_decode($inscription->metadata, true) ?? [];

            if (!empty($meta)) {
                $inscriptionsChecked++;
                if ($inscriptionsChecked <= 3) { // Solo log las primeras 3
                    \Log::info("Inscription ID {$inscription->id} - Metadata keys: " . implode(', ', array_keys($meta)));
                }
            }

            // Comprobar cada form_field
            foreach ($allFormFields as $field) {
                $fieldName = $field->name;

                // Si este campo tiene respuesta y aún no lo hemos añadido
                if (isset($meta[$fieldName]) && !empty($meta[$fieldName]) && !isset($formFieldsUsed[$fieldName])) {
                    // Obtener label traducido
                    $label = $this->getTranslatedLabel($field->label, $locale);
                    $formFieldsUsed[$fieldName] = $label;
                    \Log::info("✓ Campo '{$fieldName}' AÑADIDO con label: '{$label}' - Valor ejemplo: " . json_encode($meta[$fieldName]));
                }
            }
        }

        \Log::info("Inscripciones con metadata: {$inscriptionsChecked}");
        \Log::info("Total campos usados detectados: " . count($formFieldsUsed));
        \Log::info("Campos finales: " . json_encode($formFieldsUsed));
        \Log::info("=== FIN detectUsedFormFields ===");

        return $formFieldsUsed;
    }

    /**
     * ✨ NUEVA FUNCIÓN: Obtiene el label traducido de un form_field
     * 
     * @param mixed $jsonLabel
     * @param string $locale
     * @return string
     */
    private function getTranslatedLabel($jsonLabel, $locale)
    {
        if (empty($jsonLabel)) {
            return 'Campo sin nombre';
        }

        // Si es string, intentar decodificar
        if (is_string($jsonLabel)) {
            $decoded = json_decode($jsonLabel, true);
            if (is_array($decoded)) {
                return $decoded[$locale] ??
                    $decoded['es'] ??
                    $decoded['ca'] ??
                    array_values($decoded)[0] ??
                    $jsonLabel;
            }
            return $jsonLabel;
        }

        // Si ya es array
        if (is_array($jsonLabel)) {
            return $jsonLabel[$locale] ??
                $jsonLabel['es'] ??
                $jsonLabel['ca'] ??
                array_values($jsonLabel)[0] ??
                'Campo sin nombre';
        }

        return 'Campo sin nombre';
    }

    /**
     * ✨ NUEVA FUNCIÓN: Formatea un valor de metadata para mostrar
     * 
     * @param mixed $value
     * @return string
     */
    private function formatMetadataValue($value)
    {
        if (empty($value)) {
            return '';
        }

        // Si es un array (checkbox múltiple, etc.)
        if (is_array($value)) {
            return implode(', ', $value);
        }

        // Si es string con comas (puede ser un checkbox guardado como string)
        if (is_string($value) && strpos($value, ',') !== false) {
            return $value; // Ya está formateado
        }

        // Si es boolean
        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        // Por defecto, convertir a string
        return (string) $value;
    }

    public function liquidation($id)
    {
        abort_if(!$this->isSuperuser(), 403);

        $session = Session::findOrFail($id);
        $session->liquidation = !$session->liquidation;
        $session->save();

        Alert::success(
            $session->liquidation
                ? __('backend.session.marked_as_liquidated')
                : __('backend.session.marked_as_unliquidated')
        )->flash();

        return redirect()->back();
    }

    public function regenerate($id)
    {
        $session = Session::findOrFail($id);

        $redisService = new RedisSlotsService($session);
        $redisService->regenerateCache();

        Alert::success('Cache de slots regenerada correctamente')->flash();

        return redirect()->back();
    }

    public function listPdfErrors($id)
    {
        abort_if(!$this->isSuperuser(), 403);

        $session = Session::findOrFail($id);

        $inscriptions = $session->inscriptions()
            ->paid()
            ->get()
            ->filter(function ($inscription) {
                $pdf_path = storage_path('app/public/' . $inscription->pdf);
                return !file_exists($pdf_path);
            });

        if ($inscriptions->isEmpty()) {
            Alert::success(__('backend.session.no_pdf_errors'))->flash();
            return redirect()->back();
        }

        return view('core.session.list-pdf-errors', compact('session', 'inscriptions'));
    }

    /**
     * También actualizar el método cloneSessions para invalidar cache
     */
    public function cloneSessions(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:sessions,id',
            'sessions.*.start' => 'required|date',
            'sessions.*.end' => 'required|date|after:sessions.*.start',
        ]);

        $originalSession = Session::findOrFail($request->input('session_id'));
        $brand = get_current_brand()->code_name;
        $oldId = $originalSession->id;
        $oldFolder = "uploads/{$brand}/session/{$oldId}/";

        $clonedSessions = [];

        foreach ($request->sessions as $sessionData) {
            $clone = $originalSession->replicate();

            $clone->starts_on = Carbon::parse($sessionData['start']);
            $clone->ends_on = Carbon::parse($sessionData['end']);
            $clone->inscription_starts_on = $sessionData['inscription_starts_on'];
            $clone->inscription_ends_on = $sessionData['inscription_ends_on'];
            $clone->user_id = backpack_user()->id;

            $clone->save();
            $this->cloneRates($originalSession, $clone);

            // Regenerar cache para la nueva sesión clonada
            if ($clone->is_numbered) {
                UpdateSessionSlotCache::dispatch($clone);
            }

            // Copiar archivos si existen
            $newId = $clone->id;
            $newFolder = "uploads/{$brand}/session/{$newId}/";

            if (Storage::disk('public')->exists($oldFolder)) {
                $allFiles = Storage::disk('public')->allFiles($oldFolder);

                foreach ($allFiles as $filePath) {
                    $relative = Str::after($filePath, $oldFolder);
                    $newFile = $newFolder . $relative;

                    $parentDir = dirname($newFile);
                    if (!Storage::disk('public')->exists($parentDir)) {
                        Storage::disk('public')->makeDirectory($parentDir);
                    }

                    Storage::disk('public')->copy($filePath, $newFile);
                }
            }

            $clonedSessions[] = $clone->id;
        }

        return response()->json([
            'success' => true,
            'cloned_sessions' => $clonedSessions
        ]);
    }

    protected function cloneRates(Session $original, Session $clone): void
    {
        // 1. Clonar tarifas asignadas a la sesión (Session::class)
        foreach ($original->rates ?? [] as $rate) {
            $pivot = $rate->pivot->toArray();
            unset($pivot['id']);

            DB::table('assignated_rates')->insert(array_merge($pivot, [
                'assignated_rate_type' => Session::class,
                'assignated_rate_id' => $clone->id,
                'session_id' => $clone->id,
                'rate_id' => $rate->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // 2. Si la sesión es numerada, clonar tarifas asignadas a las zonas (Zone::class)
        if ($original->is_numbered && $original->space && $original->space->zones && $clone->space && $clone->space->zones) {
            // Mapear zonas antiguas -> nuevas
            $zoneMap = [];

            foreach ($original->space->zones as $index => $oldZone) {
                $newZone = $clone->space->zones[$index] ?? null;
                if ($newZone) {
                    $zoneMap[$oldZone->id] = $newZone->id;
                }
            }

            // Buscar tarifas asignadas a zonas de la sesión original
            $zoneRatePivots = DB::table('assignated_rates')
                ->where('session_id', $original->id)
                ->where('assignated_rate_type', Zone::class)
                ->get();

            foreach ($zoneRatePivots as $pivot) {
                if (!isset($zoneMap[$pivot->assignated_rate_id])) {
                    continue; // No hay zona nueva para este ID
                }

                DB::table('assignated_rates')->insert([
                    'rate_id' => $pivot->rate_id,
                    'assignated_rate_type' => Zone::class,
                    'assignated_rate_id' => $zoneMap[$pivot->assignated_rate_id],
                    'session_id' => $clone->id,
                    'price' => $pivot->price,
                    'max_on_sale' => $pivot->max_on_sale,
                    'max_per_order' => $pivot->max_per_order,
                    'available_since' => $pivot->available_since,
                    'available_until' => $pivot->available_until,
                    'is_public' => $pivot->is_public,
                    'is_private' => $pivot->is_private,
                    'max_per_code' => $pivot->max_per_code,
                    'validator_class' => $pivot->validator_class,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function multiCreate()
    {
        $locale = app()->getLocale();

        $events = Event::select('id', 'name')
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->getTranslation('name', $locale)
            ]);

        $spaces = Space::with(['zones:id,space_id,name'])
            ->get(['id', 'name', 'capacity'])
            ->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->getTranslation('name', $locale),
                'capacity' => $s->capacity,
                'zones' => $s->zones->map(fn($z) => [
                    'id' => $z->id,
                    'name' => $z->name,
                ]),
            ]);

        $rates = Rate::select('id', 'name')
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->getTranslation('name', $locale)
            ]);

        $tpvs = Tpv::select('id', 'name')->orderBy('name')->get();

        return view('core.session.multi-create', compact('events', 'spaces', 'rates', 'tpvs'));
    }

    /**
     * Store multiple sessions - VERSIÓN MEJORADA
     * Soporta dos modos: temporada (season) y fechas específicas (specific_dates)
     */
    public function multiStore(StoreMultiSessionRequest $request)
    {
        $data = $request->validated();
        $creationMode = $data['creation_mode'];

        // Parámetros comunes
        $globalInscStart = Carbon::parse($data['inscription_start']);

        DB::transaction(function () use ($data, $creationMode, $globalInscStart) {
            $createdSessions = [];

            if ($creationMode === 'season') {
                // ============ MODO TEMPORADA (actual) ============
                $seasonStart = Carbon::parse($data['season_start'])->startOfDay();
                $seasonEnd = Carbon::parse($data['season_end'])->endOfDay();
                $weekdays = collect($data['weekdays'])->map(fn($d) => (int) $d);

                // Recorrer cada día del rango
                for ($date = $seasonStart->copy(); $date->lte($seasonEnd); $date->addDay()) {
                    // Filtrar por días seleccionados
                    if (!$weekdays->contains($date->isoWeekday())) {
                        continue;
                    }

                    // Por cada template, crear sesión
                    foreach ($data['templates'] as $tpl) {
                        $startsOn = $date->copy()->setTimeFromTimeString($tpl['start']);
                        $endsOn = $date->copy()->setTimeFromTimeString($tpl['end']);

                        $session = $this->createSession([
                            'event_id' => $data['event_id'],
                            'space_id' => $data['space_id'],
                            'tpv_id' => $data['tpv_id'],
                            'name' => $tpl['title'],
                            'starts_on' => $startsOn,
                            'ends_on' => $endsOn,
                            'inscription_starts_on' => $globalInscStart,
                            'inscription_ends_on' => $startsOn,
                            'is_numbered' => $data['is_numbered'],
                            'max_places' => $data['max_places'],
                        ]);

                        $this->createRatesForSession($session, $data);

                        if ($session->is_numbered) {
                            $createdSessions[] = $session;
                        }
                    }
                }
            } else {
                // ============ MODO FECHAS ESPECÍFICAS (nuevo) ============
                foreach ($data['specific_dates'] as $specificDate) {
                    $date = Carbon::parse($specificDate['date']);
                    $startsOn = $date->copy()->setTimeFromTimeString($specificDate['start']);
                    $endsOn = $date->copy()->setTimeFromTimeString($specificDate['end']);

                    // ✅ Nombre personalizado o por defecto
                    $sessionName = !empty($specificDate['title'])
                        ? $specificDate['title']
                        : "Sesión " . $date->format('d/m/Y H:i');

                    $session = $this->createSession([
                        'event_id' => $data['event_id'],
                        'space_id' => $data['space_id'],
                        'tpv_id' => $data['tpv_id'],
                        'name' => $sessionName,
                        'starts_on' => $startsOn,
                        'ends_on' => $endsOn,
                        'inscription_starts_on' => $globalInscStart,
                        'inscription_ends_on' => $startsOn,
                        'is_numbered' => $data['is_numbered'],
                        'max_places' => $data['max_places'],
                    ]);

                    $this->createRatesForSession($session, $data);

                    if ($session->is_numbered) {
                        $createdSessions[] = $session;
                    }
                }
            }

            // Regenerar cache para sesiones numeradas
            foreach ($createdSessions as $session) {
                UpdateSessionSlotCache::dispatch($session);
            }
        });

        Alert::success('Sesiones creadas con éxito')->flash();
        return redirect()->to(backpack_url('session'));
    }

    /**
     * Método auxiliar para crear una sesión
     */
    private function createSession(array $data): Session
    {
        return Session::create([
            'event_id' => $data['event_id'],
            'space_id' => $data['space_id'],
            'tpv_id' => $data['tpv_id'] ?? null,
            'name' => $data['name'],
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'inscription_starts_on' => $data['inscription_starts_on'],
            'inscription_ends_on' => $data['inscription_ends_on'],
            'is_numbered' => $data['is_numbered'],
            'max_places' => $data['max_places'],
        ]);
    }

    /**
     * Método auxiliar para crear tarifas de una sesión
     */
    private function createRatesForSession(Session $session, array $data): void
    {
        if ($data['is_numbered']) {
            // Sesión numerada: tarifas por zona
            $space = Space::with('zones')->find($data['space_id']);

            if ($space->zones->isEmpty()) {
                throw new \Exception("El espacio '{$space->name}' no tiene zonas definidas.");
            }

            foreach ($data['rates'] as $rate) {
                $zoneId = !empty($rate['zone_id'])
                    ? $rate['zone_id']
                    : $space->zones->first()->id;

                AssignatedRate::create([
                    'rate_id' => $rate['rate_id'],
                    'assignated_rate_type' => Zone::class,
                    'assignated_rate_id' => $zoneId,
                    'session_id' => $session->id,
                    'price' => $rate['price'],
                    'max_on_sale' => $rate['max_on_sale'] ?? 0,
                    'max_per_order' => $rate['max_per_order'] ?? 0,
                    'is_public' => $rate['is_public'] ?? false,
                ]);
            }
        } else {
            // Sesión NO numerada: tarifas generales
            foreach ($data['rates'] as $rate) {
                AssignatedRate::create([
                    'rate_id' => $rate['rate_id'],
                    'assignated_rate_type' => Session::class,
                    'assignated_rate_id' => $session->id,
                    'session_id' => $session->id,
                    'price' => $rate['price'],
                    'max_on_sale' => $rate['max_on_sale'] ?? 0,
                    'max_per_order' => $rate['max_per_order'] ?? 0,
                    'is_public' => $rate['is_public'] ?? false,
                ]);
            }
        }
    }

    public function importCodes($id)
    {
        DB::transaction(function () use ($id) {
            // Deshabilitar los eventos de Eloquent temporalmente
            SessionCode::flushEventListeners();

            // Eliminar todos los registros de la tabla censu
            SessionCode::where('session_id', $id)->delete();

            // Importar los nuevos datos desde el archivo Excel
            Excel::import(new SessionCodeImport($id), request()->file('csv'));
        });

        return redirect()->to('/session');
    }
}
