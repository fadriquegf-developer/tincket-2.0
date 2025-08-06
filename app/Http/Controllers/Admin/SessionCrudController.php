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
use App\Jobs\RegenerateSession;
use App\Traits\AllowUsersTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\CacheSessionSlot;
use Illuminate\Http\JsonResponse;
use App\Imports\SessionCodeImport;
use App\Observers\SessionObserver;
use Illuminate\Support\Facades\DB;
use Prologue\Alerts\Facades\Alert;
use App\Events\SessionRatesUpdated;
use App\Traits\CrudPermissionTrait;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\SessionRequest;
use Backpack\CRUD\app\Library\Widget;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use App\Http\Requests\StoreMultiSessionRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;


/**
 * Class SessionCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SessionCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate;
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
        CRUD::setEntityNameStrings(__('backend.menu.session'), __('backend.menu.sessions'));
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

    // Todas las funciones de columnas y campos se definen en el trait SessionCrudUi

    public function store(SessionRequest $request)
    {


        $response = $this->traitStore();
        $session = $this->crud->getCurrentEntry();

        SessionRatesUpdated::dispatch($session);

        return $response;
    }

    public function update(SessionRequest $request)
    {
        // Obtenemos el modelo ANTES de actualizar, para capturar las rutas antiguas
        $session = $this->crud->getCurrentEntry();
        $oldImages = $session->images ?? [];

        $response = $this->traitUpdate();
        $session = $this->crud->getCurrentEntry();

        SessionObserver::processImages($session);

        //  Volvemos a leer `$session->images` ya procesadas (definitivas)
        $session->refresh();
        $newImages = $session->images ?? [];

        // Calculamos qué imágenes antiguas hay que eliminar del disco
        $toDelete = array_diff($oldImages, $newImages);

        foreach ($toDelete as $oldPath) {
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            } else {
                Log::warning('[Debug][SessionUpdate] No existe en disco, no se pudo borrar: ' . $oldPath);
            }
        }

        // Actualizar precios, slots, disparar eventos…
        if ((bool) $request->input('is_rates_table_dirty', false)) {
            $this->updatePrices($request);
            SessionRatesUpdated::dispatch($session);
        }

        if ($request->filled('slot_labels')) {
            $this->updateSlots($session);
        }

        return $response;
    }



    protected function updateSlots(Session $session): void
    {
        $locked_slots = collect(json_decode(request()->input('slot_labels')));
        $auxIdTempSlots = collect();

        // Eliminar SessionTempSlots caducados con diferencias de estado
        $session_temp_slots = \App\Models\SessionTempSlot::notExpired()->whereSessionId($session->id)->get();
        $session_temp_slots->each(function ($slot) use ($locked_slots, $auxIdTempSlots) {
            $new_slot = $locked_slots->where('id', $slot->slot_id);
            if ($new_slot->count() > 0) {
                if ($slot->expires_on === null && $new_slot->first()->status_id !== $slot->status_id) {
                    $slot->delete();
                } else {
                    $auxIdTempSlots->push($slot->slot_id);
                }
            }
        });

        // Eliminar SessionSlots que ahora están vacíos (sin estado)
        SessionSlot::whereSessionId($session->id)
            ->whereIn('slot_id', $locked_slots->where('status_id', null)->pluck('id')->toArray())
            ->delete();

        // Liberar de la caché los slots eliminados
        CacheSessionSlot::whereSessionId($session->id)
            ->whereIn('slot_id', $locked_slots->where('status_id', null)->pluck('id')->toArray())
            ->update([
                'cart_id' => null,
                'is_locked' => 0,
                'lock_reason' => null,
                'comment' => null
            ]);

        // Filtrar los slots con estado definido
        $session_slots = $locked_slots->where('status_id', '!=', null);

        // Obtener slots vendidos
        $sold_slots = \App\Models\Inscription::paid()
            ->where('session_id', $session->id)
            ->get();

        // Obtener los SessionSlots existentes
        $dbSessionSlots = SessionSlot::whereSessionId($session->id)
            ->whereIn('slot_id', $session_slots->pluck('id')->toArray())
            ->get();

        // Iterar y actualizar/crear
        $session_slots->each(function ($new_slot) use ($session, $dbSessionSlots, $sold_slots) {
            $slot = $dbSessionSlots->where('slot_id', $new_slot->id)->first();

            if ($slot) {
                if ($sold_slots->where('slot_id', $new_slot->id)->first()) {
                    $slot->status_id = 2;
                    $slot->comment = null;
                    $slot->save();
                } else {
                    if ($slot->status_id != $new_slot->status_id || ($new_slot->comment ?? null) !== $slot->comment) {
                        $slot->status_id = $new_slot->status_id;
                        $slot->comment = $new_slot->comment ?? null;
                        $slot->save();
                    }
                }
            } else {
                $status_id = $sold_slots->where('slot_id', $new_slot->id)->first() ? 2 : $new_slot->status_id;

                SessionSlot::create([
                    'session_id' => $session->id,
                    'slot_id' => $new_slot->id,
                    'status_id' => $status_id,
                    'comment' => $new_slot->comment ?? null,
                ]);
            }
        });
    }

    protected function updatePrices(Request $request)
    {
        $this->session_id = is_a($this->crud->entry, Session::class) ? $this->crud->entry->id : null;

        //  what to do when other controllers extend this one?
        if (!$this->session_id)
            throw new \Exception("Not implemented yet");

        // delete all related rates
        \DB::table('assignated_rates')->where('session_id', $this->session_id)->delete();

        if ($this->crud->entry->is_numbered) {
            $this->updateNumberedSessionPrices($request);
        } else {
            $this->updateNonNumberedSessionPrices($request);
        }
    }

    protected function updateNumberedSessionPrices(Request $request)
    {
        // 1) Borra todas las tarifas antiguas de esta sesión (tanto zones como rates)
        AssignatedRate::where('session_id', $this->session_id)->delete();

        // 2) Decodifica el JSON de Vue
        $rawRates = $request->get('rates')
            ? json_decode($request->get('rates'), true)
            : [];

        // 3) Recupera las zonas del espacio de esta sesión
        $zones = Zone::where('space_id', $this->crud->entry->space_id)->get();

        // 4) Por cada zona, filtra las filas que le corresponden y las crea
        foreach ($zones as $zone) {
            // Filtrar solo las filas asignadas a esta zona
            $filtered = array_filter($rawRates, function ($r) use ($zone) {
                return isset($r['assignated_rate_id'])
                    && $r['assignated_rate_id'] == $zone->id;
            });

            foreach ($filtered as $raw) {
                // Saltar si no hay tarifa seleccionada
                if (empty($raw['rate']['id'])) {
                    continue;
                }

                // Montar el array de datos para creación
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

                    // estos dos para el morph
                    'assignated_rate_type' => Zone::class,
                    'assignated_rate_id' => $zone->id,
                ];

                AssignatedRate::create($data);
            }
        }
    }

    protected function updateNonNumberedSessionPrices(Request $request)
    {
        // 1) Borra todas las tarifas viejas de esta sesión
        AssignatedRate::where('session_id', $this->session_id)->delete();

        // 2) Decodifica el JSON que viene de Vue
        $rawRates = $request->get('rates')
            ? json_decode($request->get('rates'), true)
            : [];

        // 3) Recorre cada fila y crea el registro en la BD
        foreach ($rawRates as $raw) {
            // Asegúrate de que raw['rate'] existe y trae un id
            if (empty($raw['rate']['id'])) {
                continue; // si no hay tarifa seleccionada, salta esta fila
            }

            // Prepara los datos en un array plano
            $data = [
                // los campos fijos
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

                // para mantener el morph (si tu pivot lo requiere)
                'assignated_rate_type' => Session::class,
                'assignated_rate_id' => $this->session_id,
            ];

            // 4) Crea el modelo en la tabla assignated_rates
            AssignatedRate::create($data);
        }
    }

    protected function updatePricesPerZone(array $rates, Zone $zone)
    {

        $rates = array_filter($rates, function ($rate) use ($zone) {
            return ($rate->assignated_rate_id ?? null) === $zone->id; // only suitable for this version where we always add prices per Zone
        });

        foreach ($rates as $rate) {
            $zone->pivot_session_id = $this->session_id;
            $this->prepareRateObject($rate);

            if ($rate->rate_id) {
                $rate->session_id = $this->session_id;
                $zone->rates()->attach(
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
                'class' => 'form-group col-xs-12 col-sm-12',
            ],
            'tab' => 'Basic',
        ]);
    }

    public function inscriptions($id)
    {
        /* 1)  Sesión + relaciones */
        $session = Session::with([
            'event',
            'space',
            'inscriptions.cart.client',
            'inscriptions.cart.payments',
            'inscriptions.rate',
            'inscriptions.slot',
        ])->findOrFail($id);

        /* 2)  Solo inscripciones con compra finalizada */
        $inscriptions = $session->inscriptions()
            ->whereHas('cart', fn($q) => $q->whereNotNull('confirmation_code'))
            ->with(['cart.client', 'cart.payments', 'rate', 'slot'])
            ->get();

        /* 3)  Contadores */
        $validated = $inscriptions->whereNotNull('checked_at')->count();
        $validatedIn = $session->count_validated - $session->count_validated_out;
        $validatedOut = $session->count_validated_out;

        $sellWebEntries = $inscriptions->filter(function ($i) {
            return in_array(
                optional($i->cart->payment)->gateway,
                ['Sermepa', 'SermepaSoapService', 'Free']
            );
        });
        $sellOfficeEntries = $inscriptions->filter(function ($i) {
            return optional($i->cart->payment)->gateway === 'TicketOffice';
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

        /* 4)  DNIs duplicados */
        $dniRepeated = $inscriptions->pluck('metadata')
            ->map(fn($m) => data_get(json_decode($m, true), 'dni'))
            ->filter()
            ->duplicates();

        $duplicates = $inscriptions->filter(function ($i) use ($dniRepeated) {
            $dni = data_get(json_decode($i->metadata, true), 'dni');
            return $dni && $dniRepeated->contains($dni);
        });

        /* 5)  Vista */
        return view(
            'core.session.inscriptions',
            compact('session', 'inscriptions', 'stats', 'duplicates')
        );
    }


    public function exportExcel($sessionId)
    {
        // 1) Cargo la sesión con sus inscripciones
        $session = Session::with([
            'inscriptions.cart.client',
            'inscriptions.cart.payments',
            'inscriptions.rate',
            'inscriptions.slot',
        ])->findOrFail($sessionId);

        // 2) Preparo los datos
        $rows = $session->inscriptions->map(function ($i) {
            $client = optional($i->cart->client);
            $meta = collect(json_decode($i->metadata, true));

            return [
                'Nombre' => $client->name,
                'Apellidos' => $client->surname,
                'Email' => $client->email,
                'Teléfono' => $client->phone,
                'Confirmación' => optional($i->cart)->confirmation_code,
                'Plataforma' => optional($i->cart->payment)->gateway,
                'Tarifa' => optional($i->rate)->name,
                'Posición en mapa' => optional($i->slot)->name ?? 'n/a',
                'Código de barras' => $i->barcode,
                'Validado' => $i->checked_at ? 'Sí' : 'No',
                'DNI' => $meta->get('dni', ''),
                'Creado' => optional($i->cart)->created_at?->format('d/m/Y H:i'),
            ];
        })->toArray();

        // 3) Clase anónima que implementa FromCollection, WithHeadings y WithEvents
        $export = new class (collect($rows)) implements FromCollection, WithHeadings, WithEvents {
            private $collection;

            public function __construct($collection)
            {
                $this->collection = $collection;
            }

            // FromCollection: filas de datos
            public function collection()
            {
                return $this->collection;
            }

            // WithHeadings: cabeceras (claves del primer elemento)
            public function headings(): array
            {
                return $this->collection->first()
                    ? array_keys($this->collection->first())
                    : [];
            }

            // WithEvents: zebra striping
            public function registerEvents(): array
            {
                return [
                    AfterSheet::class => function (AfterSheet $event) {
                        $sheet = $event->sheet->getDelegate();
                        $rowCount = $this->collection->count() + 1; // +1 por la cabecera
    
                        // Aplico zebra: filas pares (2,4,6...) con gris muy claro
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

        // 4) Descarga
        return Excel::download($export, "inscripciones-{$sessionId}.xlsx");
    }


    /**
     * Genera un PDF imprimible de las inscripciones.
     */
    public function printInscr($sessionId)
    {
        $session = Session::with([
            'inscriptions.cart.client',
            'inscriptions.cart.payments',
            'inscriptions.rate',
            'inscriptions.slot',
        ])->findOrFail($sessionId);

        $inscriptions = $session->inscriptions;

        // Crea la vista resources/views/admin/session/print-inscriptions.blade.php
        $pdf = Pdf::loadView('core.session.print-inscriptions', compact('session', 'inscriptions'));
        return $pdf->stream("inscripciones-{$sessionId}.pdf");
    }

    public function liquidation($id)
    {
        // sólo superadmins
        abort_if(!$this->isSuperuser(), 403);

        $session = Session::findOrFail($id);

        // cambia el booleano
        $session->liquidation = !$session->liquidation;
        $session->save();

        // feedback opcional
        Alert::success(
            $session->liquidation
            ? __('backend.session.marked_as_liquidated')
            : __('backend.session.unmarked_as_liquidated')
        )->flash();

        return redirect()->back();
    }

    public function regenerate($id)
    {
        // Borra todas las entradas de CacheSessionSlot para esta sesión
        CacheSessionSlot::where('session_id', $id)->delete();

        // Recupera la sesión y dispara el job
        $session = Session::findOrFail($id);
        RegenerateSession::dispatch($session);

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

    public function cloneSessions(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:sessions,id',
            'sessions.*.start' => 'required|date',
            'sessions.*.end' => 'required|date|after:sessions.*.start',
        ]);

        /** @var \App\Models\Session $originalSession */
        $originalSession = Session::findOrFail($request->input('session_id'));

        $brand = get_current_brand()->code_name;
        $oldId = $originalSession->id;
        $oldFolder = "uploads/{$brand}/session/{$oldId}/";

        foreach ($request->sessions as $sessionData) {
            $clone = $originalSession->replicate();

            $clone->starts_on = Carbon::parse($sessionData['start']);
            $clone->ends_on = Carbon::parse($sessionData['end']);
            $clone->inscription_starts_on = $sessionData['inscription_starts_on'];
            $clone->inscription_ends_on = $sessionData['inscription_ends_on'];
            $clone->user_id = backpack_user()->id;

            $clone->save();


            $this->cloneRates($originalSession, $clone);

            $newId = $clone->id;
            $newFolder = "uploads/{$brand}/session/{$newId}/";

            // 3.1) Si la carpeta del original existe, listamos todos los archivos dentro
            if (Storage::disk('public')->exists($oldFolder)) {
                $allFiles = Storage::disk('public')->allFiles($oldFolder);

                foreach ($allFiles as $filePath) {

                    $relative = Str::after($filePath, $oldFolder);

                    $newFile = $newFolder . $relative;

                    $parentDir = dirname($newFile);
                    if (!Storage::disk('public')->exists($parentDir)) {
                        Storage::disk('public')->makeDirectory($parentDir);
                    }

                    // Copiamos el archivo
                    Storage::disk('public')->copy($filePath, $newFile);
                }
            }
        }

        return response()->json(['success' => true]);
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

    public function multiStore(StoreMultiSessionRequest $request)
    {
        // 1. VALIDACIÓN ---------------------------------------------------------
        $data = $request->validated();

        // 2. PARAMETROS DE FECHA -------------------------------------------------
        $seasonStart = Carbon::parse($data['season_start'])->startOfDay();
        $seasonEnd = Carbon::parse($data['season_end'])->endOfDay();
        $weekdays = collect($data['weekdays'])->map(fn($d) => (int) $d);
        $globalInscStart = Carbon::parse($data['inscription_start']);


        // 3. CREACIÓN EN UNA TRANSACCIÓN ----------------------------------------
        DB::transaction(function () use ($data, $seasonStart, $seasonEnd, $weekdays, $globalInscStart) {

            // Recorre cada día del rango
            for ($date = $seasonStart->copy(); $date->lte($seasonEnd); $date->addDay()) {

                // Filtra por los días marcados (1 = lunes … 7 = domingo)
                if (!$weekdays->contains($date->isoWeekday())) {
                    continue;
                }

                // Por cada plantilla diaria…
                foreach ($data['templates'] as $tpl) {

                    // Combina la fecha del bucle con las horas definidas en la plantilla
                    $startsOn = $date->copy()->setTimeFromTimeString($tpl['start']);
                    $endsOn = $date->copy()->setTimeFromTimeString($tpl['end']);

                    // Crea la sesión
                    $session = Session::create([
                        'event_id' => $data['event_id'],
                        'space_id' => $data['space_id'],
                        'tpv_id' => $data['tpv_id'],
                        'name' => $tpl['title'],

                        'starts_on' => $startsOn,
                        'ends_on' => $endsOn,

                        // --- reglas de inscripción solicitadas --------------
                        'inscription_starts_on' => $globalInscStart,
                        'inscription_ends_on' => $startsOn,
                        // -----------------------------------------------------

                        'is_numbered' => $data['is_numbered'],
                        'max_places' => $data['max_places'],
                    ]);

                    // Asigna las tarifas
                    foreach ($data['rates'] as $rate) {
                        $session->rates()->attach($rate['rate_id'], [
                            'price' => $rate['price'],
                            'max_on_sale' => $rate['max_on_sale'] ?? 0,
                            'max_per_order' => $rate['max_per_order'] ?? 0,
                            'is_public' => $rate['is_public'] ?? false,
                        ]);
                    }
                }
            }
        });

        Alert::success('Sesiones creadas con éxito')->flash();
        return redirect()->to(backpack_url('session'));
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
