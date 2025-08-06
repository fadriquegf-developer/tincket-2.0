<div class='space-layout-legend'>
    <ul class='legend-labels'>
        <li><span class="slot-available"></span>{{ __('backend.svg_layout.legend.available') }}</li>
        <li><span class="slot-selected"></span>{{ __('backend.svg_layout.legend.selected') }}</li>
        <li><span class="slot-sold"></span>{{ __('backend.svg_layout.legend.sold') }}</li>
        <li><span class="slot-booked"></span>{{ __('backend.svg_layout.legend.booked') }}</li>
        <li><span class="slot-booked-packs"></span>{{ __('backend.svg_layout.legend.booked_packs') }}</li>
        <li><span class="slot-hidden"></span>{{ __('backend.svg_layout.legend.hidden') }}</li>
        <li><span class="slot-locked"></span>{{ __('backend.svg_layout.legend.locked') }}</li>
        <li><span class="slot-covid19"></span>{{ __('backend.svg_layout.legend.covid19') }}</li>
        <li><span class="slot-disability"></span>{{ __('backend.svg_layout.legend.disability') }}</li>
    </ul>
</div>

@push('after_styles')
    <style>
        /* ---------- Colores de la leyenda  */
        .slot-available {
            background: #0c860f
        }

        .slot-selected {
            background: #000
        }

        .slot-sold {
            background: #e53935
        }

        .slot-booked {
            background: #800080
        }

        .slot-booked-packs {
            background: #0f6fb2
        }

        .slot-hidden {
            background: #ffffff
        }

        .slot-locked {
            background: #fea90d
        }

        .slot-covid19 {
            background: #816fef
        }

        .slot-disability {
            background: #dcdcdd
        }
    </style>
@endpush