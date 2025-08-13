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
        <div class="col-md-12">
            <div ng-app="ticketOfficeApp">
                <form action="{{ route('ticket-office.store') }}" method="POST">
                    {{ csrf_field() }}
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                @include('core.ticket-office.inc.inscriptions')
                            </div>
                            <div class="mb-3">
                                @include('core.ticket-office.inc.packs')
                            </div>
                            @can('manage_gift_cards')
                                <div class="mb-3">
                                    @include('core.ticket-office.inc.gift_cards')
                                </div>
                            @endcan
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                @include('core.ticket-office.inc.client')
                            </div>
                            <div class="mb-3">
                                @include('core.ticket-office.inc.payment')
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <input type="submit" class="btn btn-lg btn-success btn-confirm"
                                value="{{ trans('ticket-office.confirm_cart') }}" />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @if ($sessions->isNotEmpty())
        @include('core.ticket-office.inc.loading')
    @endif
@endsection

@section('before_styles')
    {{-- Bootstrap 5 and dependencies --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css" rel="stylesheet"
        type="text/css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/zoomist@2/zoomist.css" />
    <style>
        /* Zoomist container styles */
        .zoomist-container {
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

        /* Loading overlay */
        #loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }

        #loading-image {
            width: 64px;
            height: 64px;
        }

        /* Selection area styles */
        .selection-area {
            background: rgba(46, 115, 252, 0.11);
            border: 2px solid rgba(98, 155, 255, 0.81);
            border-radius: 0.25rem;
        }

        .slot.selected {
            stroke: #ff9800;
            stroke-width: 2;
        }

        .slot.free {
            cursor: pointer;
        }

        .slot.free:hover {
            opacity: 0.8;
        }

        .border-slot {
            stroke: #000;
            stroke-width: 1;
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
        }
    </style>
@endsection

@section('after_scripts')
    @parent
    <script src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.13.6/underscore-min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/angular.js/1.8.3/angular.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/angular-ui-sortable/0.19.0/sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/zoomist@2/zoomist.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@simonwep/selection-js/dist/selection.min.js"></script>
    <script type="text/javascript">
        // Disable submitting form on enter pressed
        $(document).on('keypress', 'form', function(e) {
            if (e.which == 13) {
                return false;
            }
        });

        $(document).on('submit', 'form', function() {
            $(".btn-confirm").attr("disabled", true);
            return true;
        });

        // Status configuration
        var slotStatus = {!! json_encode(\App\Models\Status::where('id', '!=', 6)->get()->pluck('name', 'id')) !!};

        // Sessions data for Angular
        @if ($json_sessions->count() > 0)
            window.sessions_list = {!! $json_sessions->toJson() !!};
        @else
            window.sessions_list = [{
                space: {
                    layout: ''
                }
            }];
        @endif
    </script>

    {{-- Angular services and controllers --}}
    <script src="{{ asset('js/crud/ticket-office/angular-services.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/crud/ticket-office/angular-create-inscriptions.js') }}?v={{ time() }}"></script>

    @stack('after_scripts')
@endsection
