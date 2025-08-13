<div class='space-layout-legend'>
    <ul class='legend-labels list-unstyled d-flex flex-wrap gap-3 mb-0'>
        <li class="d-flex align-items-center gap-2">
            <span class="slot-available"></span>
            <small>{{ __('ticket-office.svg_layout.legend.available') }}</small>
        </li>
        <li class="d-flex align-items-center gap-2">
            <span class="slot-selected"></span>
            <small>{{ __('ticket-office.svg_layout.legend.selected') }}</small>
        </li>
        <li class="d-flex align-items-center gap-2">
            <span class="slot-sold"></span>
            <small>{{ __('ticket-office.svg_layout.legend.sold') }}</small>
        </li>
        <li class="d-flex align-items-center gap-2">
            <span class="slot-booked"></span>
            <small>{{ __('ticket-office.svg_layout.legend.booked') }}</small>
        </li>
        <li class="d-flex align-items-center gap-2">
            <span class="slot-booked-packs"></span>
            <small>{{ __('ticket-office.svg_layout.legend.booked_packs') }}</small>
        </li>
        <li class="d-flex align-items-center gap-2">
            <span class="slot-hidden"></span>
            <small>{{ __('ticket-office.svg_layout.legend.hidden') }}</small>
        </li>
        <li class="d-flex align-items-center gap-2">
            <span class="slot-locked"></span>
            <small>{{ __('ticket-office.svg_layout.legend.locked') }}</small>
        </li>
        <li class="d-flex align-items-center gap-2">
            <span class="slot-covid19"></span>
            <small>{{ __('ticket-office.svg_layout.legend.covid19') }}</small>
        </li>
        <li class="d-flex align-items-center gap-2">
            <span class="slot-disability"></span>
            <small>{{ __('ticket-office.svg_layout.legend.disability') }}</small>
        </li>
    </ul>
</div>

@push('after_styles')
    <style>
        /* Legend color styles - Bootstrap 5 compatible */
        .legend-labels span {
            width: 20px;
            height: 20px;
            border-radius: 0.25rem;
            display: inline-block;
            border: 1px solid rgba(0, 0, 0, .125);
        }

        .slot-available {
            background-color: #198754;
        }

        .slot-selected {
            background-color: #212529;
        }

        .slot-sold {
            background-color: #dc3545;
        }

        .slot-booked {
            background-color: #6f42c1;
        }

        .slot-booked-packs {
            background-color: #0d6efd;
        }

        .slot-hidden {
            background-color: #ffffff;
            border: 2px solid #dee2e6 !important;
        }

        .slot-locked {
            background-color: #fd7e14;
        }

        .slot-covid19 {
            background-color: #6610f2;
        }

        .slot-disability {
            background-color: #6c757d;
        }

        /* Responsive legend layout */
        @media (max-width: 768px) {
            .legend-labels {
                flex-direction: column;
                gap: 0.5rem !important;
            }
        }
    </style>
@endpush
