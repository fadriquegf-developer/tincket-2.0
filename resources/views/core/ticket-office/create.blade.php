@extends(backpack_view('blank'))

@php
  $defaultBreadcrumbs = [
    trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
    trans('ticket-office.tickets_office') => route('ticket-office.create'),
    trans('ticket-office.create') => false,
  ];

  // if breadcrumbs aren't defined in the CrudController, use the default breadcrumbs
  $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
    <section class="header-operation container-fluid animated fadeIn d-flex mb-2 align-items-baseline d-print-none" bp-section="page-header">
        <h1 class="text-capitalize mb-0" bp-section="page-heading">
            {{ trans('ticket-office.tickets_office') }} {{ Request::get('show_expired') ? trans('ticket-office.all_sessions') : ''}}
        </h1>
        <p class="ms-2 ml-2 mb-0" bp-section="page-subheading">{{ trans('ticket-office.create') }}</p>
    </section>
@endsection

@section('content')

@include('core.ticket-office.inc.form_old_values')
<?php $old_data = $old_data ?? []; ?>

<?php
// Calcule rate->count_free_positions consume a lot of resources  
// To prevent Maximum execution time error with large number of session
// Not calcule count_free_positions
$calculeFreePositions = $sessions->count() < 100;

$json_sessions = $sessions->map(function($session) use($calculeFreePositions)
{
    return [
        'id' => $session->id,
        'name' => (sprintf("%s %s (%s)", $session->event->name, $session->name, $session->starts_on)),
        'is_numbered' => $session->is_numbered,
        'is_past' => $session->ends_on < \Carbon\Carbon::now(),
        'rates' => $session->rates->map(function($rate) use ($calculeFreePositions)
                {
                    $max_per_order = $calculeFreePositions ? $rate->count_free_positions : 0;
                    return [
                        'id' => $rate->id,
                        'name' => [
                            config('app.locale') => $rate->name
                        ],
                        'available' => max(0, $max_per_order),
                        'price' => $rate->pivot->price
                    ];
                }),
        'space' => [
            'layout' => asset(\Storage::url($session->space->svg_path))
        ]
    ];
});
?>

@include('core.ticket-office.inc.form_errors')

<div class="row" bp-section="ticket-office-operation">
    <div class="col-md-12">
        <div ng-app="ticketOfficeApp">
            <form action="{{ route('ticket-office.store')}}" method="POST">
                {{ csrf_field()}}
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
                                                        <input type="submit" class="btn btn-lg btn-success btn-confirm" value="{{ trans('ticket-office.confirm_cart') }}"/>
                    </div>               
                </div>
            </form>         
        </div>
    </div>
</div>

@if($sessions->isNotEmpty())
@include('core.ticket-office.inc.loading')
@endif

@endsection 

@section('before_styles')
    {{-- Backpack 6 compatibility --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" rel="stylesheet" type="text/css" /> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/zoomist@2/zoomist.css" />
    <style>
        .zoomist-container {
            width: 100%;
        }

        .zoomist-wrapper{
            background: transparent;
        }

        .zoomist-image {
            height: 100%;
            pointer-events: auto;
        }

        /* Backpack 6 + Bootstrap 5 adjustments for ticket office */
        .box {
            background: #fff;
            border-radius: 0.375rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
            border: 1px solid rgba(0,0,0,.125);
            color: #212529;
            display: block;
            padding: 1.25rem;
            margin-bottom: 1rem;
        }

        .box-header {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 0.75rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            padding: 0;
        }

        /* Bootstrap 5 form adjustments */
        .form-horizontal .row {
            margin-bottom: 1rem;
        }

        .form-horizontal .form-label {
            font-weight: 500;
        }

        /* Table responsive adjustments */
        .table-responsive {
            border-radius: 0.375rem;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            background-color: #f8f9fa;
        }

        /* Input group adjustments */
        .input-group-sm .btn {
            font-size: 0.875rem;
        }

        /* Alert adjustments */
        .alert {
            border: 0;
            border-radius: 0.375rem;
        }

        /* Modal adjustments for Bootstrap 5 */
        .modal-dialog {
            max-width: 95%;
        }

        @media (min-width: 576px) {
            .modal-dialog {
                max-width: 90%;
            }
        }

        /* Custom styles for wrapper-rate (counter) */
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
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
            line-height: 1;
        }

        .wrapper-rate .minus:hover,
        .wrapper-rate .plus:hover {
            background: #495057;
        }

        .wrapper-rate .num {
            font-weight: 600;
            min-width: 60px;
            text-align: center;
        }

        /* Space layout legend Bootstrap 5 */
        .space-layout-legend {
            margin-top: 1rem;
        }

        .legend-labels {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .legend-labels li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .legend-labels span {
            width: 20px;
            height: 20px;
            border-radius: 0.25rem;
            display: inline-block;
        }
    </style>
@endsection 

@section('after_scripts') 
    @parent
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.8.3/underscore-min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/angular.js/1.5.8/angular.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/angular-ui-sortable/0.14.3/sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/zoomist@2/zoomist.umd.js"></script>
    <script type="text/javascript">
        // Disable submitting form on enter pressed
        $("form").keypress(function(e) {
            // Enter key
            if (e.which == 13) {
                return false;
            }
        });

        $("form").submit(function() {
            $(".btn-confirm").attr("disabled", true);
            return true;
        });
        
        var slotStatus = {!! json_encode(\App\Models\Status::where('id','!=',6)->get()->pluck('name', 'id')) !!}
    </script>

    <script src="https://cdn.jsdelivr.net/npm/@simonwep/selection-js/dist/selection.min.js"></script>
    {{-- In order to be able to push scripts from partials (inscriptions, client, etc.) --}} 
    @stack('after_scripts') 
    <script type="text/javascript" src="{{ asset('js/crud/ticket-office/angular-services.js')}}?v={{ time() }}"></script>
@endsection