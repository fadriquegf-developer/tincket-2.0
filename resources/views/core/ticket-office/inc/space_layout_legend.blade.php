<div class='space-layout-legend'>
    <ul class='legend-labels'>
        <li><span class="slot-available"></span>{{ __('ticket-office.svg_layout.legend.available') }}</li>
        <li><span class="slot-selected"></span>{{ __('ticket-office.svg_layout.legend.selected') }}</li>
        <li><span class="slot-sold"></span>{{ __('ticket-office.svg_layout.legend.sold') }}</li>
        <li><span class="slot-booked"></span>{{ __('ticket-office.svg_layout.legend.booked') }}</li>
        <li><span class="slot-booked-packs"></span>{{ __('ticket-office.svg_layout.legend.booked_packs') }}</li>
        <li><span class="slot-hidden"></span>{{ __('ticket-office.svg_layout.legend.hidden') }}</li>
        <li><span class="slot-locked"></span>{{ __('ticket-office.svg_layout.legend.locked') }}</li>
        <li><span class="slot-covid19"></span>{{ __('ticket-office.svg_layout.legend.covid19') }}</li>
        <li><span class="slot-disability"></span>{{ __('ticket-office.svg_layout.legend.disability') }}</li>
    </ul>
</div>

@push('after_styles')
    <style>
        /* ---------- Colores de la leyenda Bootstrap 5 */
        .slot-available {
            background: #198754;
        }

        .slot-selected {
            background: #212529;
        }

        .slot-sold {
            background: #dc3545;
        }

        .slot-booked {
            background: #6f42c1;
        }

        .slot-booked-packs {
            background: #0d6efd;
        }

        .slot-hidden {
            background: #ffffff;
            border: 1px solid #dee2e6;
        }

        .slot-locked {
            background: #fd7e14;
        }

        .slot-covid19 {
            background: #6610f2;
        }

        .slot-disability {
            background: #6c757d;
        }

        /* Responsive legend */
        @media (max-width: 768px) {
            .legend-labels {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
@endpush