@php
    /* ----------------------------------------------------------
     | Preparar los datos que necesita el widget
     ----------------------------------------------------------*/
    $entry = $crud->getCurrentEntry();

    /* 1️⃣  Zonas disponibles para la sesión numerada */
    $zones = [];
    if ($entry && $entry->is_numbered) {
        $zones = $entry->space
            ? $entry->space
                ->zones()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get()
                ->toArray()
            : [];
    }

    /* 2️⃣  Tarifas definidas para la marca (traducidas) */
    $locale = app()->getLocale();
    $definedRates = \App\Models\Rate::query()
        ->orderBy('lft')
        ->get()
        ->map(function ($rate) use ($locale) {
            return [
                'id' => $rate->id,
                'name' => $rate->getTranslation('name', $locale) ?? $rate->name,
                'needs_code' => $rate->needs_code,
                'validator_class' => $rate->validator_class,
                'validator_class_attr' => [], // se completará desde pivots
            ];
        })
        ->values()
        ->all();

    /* 3️⃣  Tarifas ya asignadas: reutilizamos los mismos objetos de definedRates */
    $initial = [];
    $pivots = \App\Models\AssignatedRate::with('rate')
        ->where('session_id', $entry->id)
        ->get();

    foreach ($pivots as $pivot) {
        // encuentra en definedRates el mismo array por id
        $rateDef = collect($definedRates)
            ->first(fn($d) => $d['id'] == $pivot->rate->id);

        if (!$rateDef) {
            continue; // tarifa eliminada o no existe
        }

        // clona para no mutar el original y añade attrs de pivot
        $rateObj = $rateDef;
        $rawValidator = $pivot->validator_class;

        // si es string lo decodificamos, si no es array usamos directo
        if (is_string($rawValidator)) {
            $decoded = json_decode($rawValidator, true);
        } elseif (is_array($rawValidator)) {
            $decoded = $rawValidator;
        } else {
            $decoded = [];
        }

        $rateObj['validator_class_attr'] = $decoded['attr'] ?? [];

        $initial[] = [
            'assignated_rate_id' => $pivot->assignated_rate_id,
            'rate' => $rateObj,
            'price' => $pivot->price,
            'max_on_sale' => $pivot->max_on_sale,
            'max_per_order' => $pivot->max_per_order,
            'available_since' => $pivot->available_since ? \Carbon\Carbon::parse($pivot->available_since)->format('Y-m-d\TH:i') : null,
            'available_until' => $pivot->available_until ? \Carbon\Carbon::parse($pivot->available_until)->format('Y-m-d\TH:i') : null,
            'is_public' => (bool) $pivot->is_public,
            'is_private' => (bool) $pivot->is_private,
            'max_per_code' => $pivot->max_per_code,
        ];
    }

    $translations = [
        'zone' => __('backend.rate.zone'),
        'rate' => __('backend.cart.rate'),
        'total' => __('backend.rate.totalavailablity'),
        'per_insc' => __('backend.rate.availabilityperinscription'),
        'price' => __('backend.ticket.price'),
        'web' => __('backend.rate.availableonweb'),
        'identif' => __('backend.rate.availableonidentification'),
        'add_rate' => __('backend.multi_session.btn_add') . __('backend.cart.rate'),
        'actions' => '', // si quieres un título de columna vacío
        'discount_code' => __('backend.rate.discount_code'),
        'max_per_user' => __('backend.rate.max_per_user'),
    ];

    /* 4️⃣  Preparamos el JSON que pasará Vue */
    $props = json_encode([
        'name' => $field['name'],
        'definedRates' => $definedRates,
        'zones' => $zones,
        'initial' => $initial,
        'isNumbered' => (int) ($entry->is_numbered ?? 0),
        'translations' => $translations,
    ]);
@endphp

{{-- ─────────────── Contenedor del componente Vue ─────────────── --}}
<div id="rates-field-{{ $field['name'] }}" data-props='{{ $props }}'></div>

{{-- Inputs ocultos que Backpack enviará --}}
<input type="hidden" name="rates" id="rates-json-{{ $field['name'] }}">
<input type="hidden" name="is_rates_table_dirty" value="0" id="rates-dirty-{{ $field['name'] }}">

@push('after_scripts')
    {{-- ─────────────── MODAL (igual al antiguo) ─────────────── --}}
    <div class="modal fade" id="importCodesModal" tabindex="-1" role="dialog" aria-labelledby="importCodesModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="alert alert-info">
                                {{ __('backend.rate.info_codes') }}
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        {{-- Máximo por código --}}
                        <div class="col-md-4">
                            <div class="form-group m-t-15">
                                <label>{{ __('backend.rate.maxcode') }}</label><br>
                                <input type="number" class="form-control" id="modal_max_per_code" />
                            </div>
                        </div>
                        {{-- Disponible desde --}}
                        <div class="col-md-4">
                            <div class="form-group m-t-15">
                                <label>{{ __('backend.rate.available_since') }}</label><br>
                                <input type="datetime-local" class="form-control" id="modal_available_since" />
                            </div>
                        </div>
                        {{-- Disponible hasta --}}
                        <div class="col-md-4">
                            <div class="form-group m-t-15">
                                <label>{{ __('backend.rate.available_until') }}</label><br>
                                <input type="datetime-local" class="form-control" id="modal_available_until" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        {{ __('backend.rate.close') }}
                    </button>
                    <button type="button" class="btn btn-primary" id="modal_save_codes">
                        {{ __('backend.rate.save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Vue 3 y SortableJS (CDN global) --}}
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    {{-- Nuestro widget Vue autónomo --}}
    @vite('resources/js/vue/rates-field.js')
@endpush