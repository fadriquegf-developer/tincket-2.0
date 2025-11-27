@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
        trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
        trans('ticket-office.tickets_office') => route('ticket-office.create'),
        trans('ticket-office.create') => false,
    ];
    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
    <section class="header-operation container-fluid animated fadeIn d-flex mb-2 align-items-baseline d-print-none"
        bp-section="page-header">
        <h1 class="text-capitalize mb-0" bp-section="page-heading">
            {{ trans('ticket-office.tickets_office') }}
            {{ Request::get('show_expired') ? trans('ticket-office.all_sessions') : '' }}
        </h1>
        <p class="ms-2 mb-0" bp-section="page-subheading">{{ trans('ticket-office.create') }}</p>
    </section>
@endsection

@section('content')
    @include('core.ticket-office.inc.form_old_values')
    @include('core.ticket-office.inc.form_errors')
    
    <div class="row" bp-section="ticket-office-operation">
        <div class="col-12">
            <div id="ticket-office-app">
                <!-- Vue 3 app will mount here -->
                <div class="text-center p-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p>Cargando aplicación...</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('before_styles')
    {{-- Bootstrap 5 and dependencies --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css" rel="stylesheet"
        type="text/css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/zoomist@2/zoomist.css" />
    
    <style>
        /* Zoomist container styles */
        .zoomist-container-inscription,
        .zoomist-container-pack,
        .zoomist-container-gift {
            width: 100%;
            max-height: 600px;
        }

        .zoomist-wrapper {
            background: #f8f9fa;
            border-radius: 0.375rem;
        }

        .zoomist-image {
            height: 100%;
            pointer-events: auto;
        }

        /* Bootstrap 5 Card Box styles for ticket office */
        .box {
            background: #fff;
            border-radius: 0.375rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, .075);
            border: 1px solid rgba(0, 0, 0, .125);
            display: block;
            margin-bottom: 1rem;
        }

        .box-header {
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
            border-radius: 0.375rem 0.375rem 0 0;
        }

        .box-title {
            color: #212529;
            display: block;
            font-size: 1.25rem;
            font-weight: 600;
            line-height: 1.125;
            margin-bottom: 0;
        }

        .box-tools {
            margin-left: auto;
        }

        .box-body {
            padding: 1.25rem;
        }

        /* Table adjustments for Bootstrap 5 */
        .table-responsive {
            border-radius: 0.375rem;
            overflow: auto;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table-secondary {
            --bs-table-bg: #e9ecef;
        }

        /* Custom counter styles */
        .wrapper-rate {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .wrapper-rate .minus,
        .wrapper-rate .plus {
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 0.25rem;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.25rem;
            line-height: 1;
            transition: background-color 0.15s ease-in-out;
        }

        .wrapper-rate .minus:hover,
        .wrapper-rate .plus:hover {
            background: #495057;
        }

        .wrapper-rate .num {
            font-weight: 600;
            min-width: 80px;
            text-align: center;
            font-size: 1.1rem;
        }

        /* Selection area styles */
        .selection-area {
            background: rgba(46, 115, 252, 0.11);
            border: 2px solid rgba(98, 155, 255, 0.81);
            border-radius: 0.25rem;
        }

        .slot.selected {
            stroke: #ff9800 !important;
            stroke-width: 2 !important;
        }

        .slot.free {
            cursor: pointer;
        }

        .slot.free:hover {
            opacity: 0.8;
        }

        .border-slot {
            stroke: #000 !important;
            stroke-width: 1 !important;
        }

        /* Modal adjustments for Bootstrap 5 */
        .modal-dialog {
            max-width: 95%;
        }

        .modal-xl {
            max-width: 1140px;
        }

        @media (min-width: 992px) {
            .modal-xl {
                max-width: 90%;
            }
        }

        /* Alert icon spacing */
        .alert i {
            margin-right: 0.5rem;
        }

        /* Form label adjustments */
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        /* Button disabled state */
        .btn:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

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

        /* Space layout styles */
        .space-layout-container {
            width: 100%;
            height: 100%;
            position: relative;
        }

        .space-layout-container object,
        .space-layout-container svg {
            width: 100%;
            height: auto;
            max-height: 500px;
        }

        .modal-backdrop{
            display: none !important;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .box-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .box-tools {
                margin-top: 1rem;
                margin-left: 0;
            }

            .legend-labels {
                flex-direction: column;
                gap: 0.5rem !important;
            }
        }
    </style>
@endsection

@section('after_scripts')
    @parent
    <script src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.13.6/underscore-min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/zoomist@2/zoomist.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@simonwep/selection-js/dist/selection.min.js"></script>
    
    <script type="text/javascript">
        // Setup CSRF for jQuery
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Disable submitting form on enter pressed
        $(document).on('keypress', 'form', function(e) {
            if (e.which == 13) {
                return false;
            }
        });

        // ✨ AÑADIR: Traducciones de Laravel para Vue
        window.translations = @json($translations);
        window.currentLocale = '{{ app()->getLocale() }}';
        
        // Status configuration
        window.slotStatus = {!! json_encode(\App\Models\Status::where('id', '!=', 6)->get()->pluck('name', 'id')) !!};

        // Sessions data for Vue
        @if ($json_sessions->count() > 0)
            window.sessions_list = {!! $json_sessions->toJson() !!};
        @else
            window.sessions_list = [{
                space: {
                    layout: ''
                }
            }];
        @endif

        // Vue 3 app props
        window.vueAppProps = {
            storeRoute: "{{ route('ticket-office.store') }}",
            canManageGiftCards: @json(auth()->user()->can('manage_gift_cards')),
            oldData: @json($old_data ?? [])
        };

        // Packs data for Vue (if needed)
        @if(isset($packs) && $packs->count() > 0)
            window.packs_list = @json($packs);
        @else
            window.packs_list = [];
        @endif
    </script>

    {{-- Cargar Vue 3 App --}}
    @vite(['resources/js/ticket-office/main.js'])

    @stack('after_scripts')

@endsection