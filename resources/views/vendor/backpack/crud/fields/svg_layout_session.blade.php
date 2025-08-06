{{-- ---------- PHP helpers ---------- --}}
@php
    $svgUrl = Storage::url($entry->space->svg_path);
    $statusColors = __('backend.svg_layout.seat_status_colors');
@endphp

<div id="svg-layout" {{-- wrapper con position:relative --}} data-svg-url="{{ $svgUrl }}"
    data-slots='@json($slots_map ?? [])' data-zones='@json($zones_map)' data-zoom-enabled="{{ $entry->zoom ? 1 : 0 }}">

    {{-- ① Div que Vue controlará (solo el plano) --}}
    <div id="svg-canvas"></div>

    {{-- ② Hidden para los cambios --}}
    <input type="hidden" name="slot_labels" id="slot_labels_input" value="[]">

    {{-- ③ Botones --}}
    <div class="btn-map-wrap">
        @if($entry->zoom)
            <button type="button" class="btn btn-primary btn-zoom-in" title="Acercar">
                <i class="la la-search-plus"></i>
            </button>
            <button type="button" class="btn btn-primary btn-zoom-out" title="Alejar">
                <i class="la la-search-minus"></i>
            </button>
            <button type="button" class="btn btn-primary btn-reset-zoom" title="Reset zoom">
                <i class="la la-refresh"></i>
            </button>
        @endif
        <button type="button" class="btn btn-primary selection-btn-edit">
            <i class="la la-pencil"></i>
        </button>
    </div>

    {{-- ④ Ayuda --}}
    <p class="help-block text-left">{!! trans('backend.svg_layout.help') !!}</p>
</div>

{{-- ---------- Leyenda ---------- --}}
@include('core.ticket-office.space_layout_legend')



{{-- ---------- Estilos ---------- --}}
@push('after_styles')
    <style>
        /* ---------- Layout básico ---------- */
        #svg-layout {
            position: relative;
            overflow: visible;
        }

        #svg-layout svg {
            max-width: 90%;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .btn-map-wrap {
            position: absolute;
            top: 0px;
            left: -65px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            z-index: 20;
        }

        .btn-map-wrap .btn {
            padding: 6px 10px
        }

        /* ---------- Leyenda ---------- */
        .space-layout-legend .legend-labels {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin: 0;
            padding: 0
        }

        .space-layout-legend .legend-labels li {
            display: flex;
            align-items: center;
            font-size: .85rem
        }

        .space-layout-legend .legend-labels li span {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 1px solid #555;
            margin-right: 6px;
            border-radius: 2px
        }

        .keyboard-key {
            display: inline-block;
            padding: 0 4px;
            margin: 0 2px;
            border: 1px solid #9e9e9e;
            border-radius: 3px;
            background: #f5f5f5;
            font-family: monospace;
            font-size: .85em;
        }

        /* ---------- Rectángulo de arrastre ---------- */
        .drag-select-rect {
            position: absolute;
            pointer-events: none;
            background: rgba(100, 120, 255, 0.15);
            border: 1px solid #5b9dff;
            z-index: 10;
        }


        /* ---------- Resaltado de selección ---------- */
        .slot.selected {
            stroke-width: 3 !important;
            stroke: #000 !important
        }
    </style>
@endpush

@push('after_scripts')
    @include('crud::fields.svg_modal_slot')
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script src="https://unpkg.com/@panzoom/panzoom@4.6.0/dist/panzoom.min.js"></script>
    <script
        src="{{ asset('js/vue/svg-layaout-session.js') }}?v={{ filemtime(public_path('js/vue/svg-layaout-session.js')) }}"></script>
@endpush