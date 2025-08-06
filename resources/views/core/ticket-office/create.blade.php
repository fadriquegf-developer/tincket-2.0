@extends('backpack::layout') @section('header')
<section class="content-header">
    <h1>
            {{ trans('tincket/backend.ticket.tickets_office') }} {{ Request::get('show_expired') ? trans('tincket/backend.ticket.all_sessions') : ''}}
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ url(config('backpack.base.route_prefix'), 'dashboard')}}">{{ trans('backpack::crud.admin')}}</a></li>
        <li><a href="{{ route('ticket-office.create')}}" class="text-capitalize">{{ trans('tincket/backend.ticket.tickets_office') }}</a></li>
        <li class="active">{{ trans('tincket/backend.ticket.create') }}</li>
    </ol>
</section>
@endsection
@section('content')
@include('core.ticket-office.inc.form_old_values')
<?php $old_data = $this->old_data ?? []; ?>

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
<div ng-app="ticketOfficeApp">
    <div class="" @include('crud::inc.field_wrapper_attributes')>
         <form action="{{ route('ticket-office.store')}}" method="POST">
            {{ csrf_field()}}
            <div class="row">
                <div class="col-lg-6">
                    <div class="row">
                        <div class="col-xs-12">
                            @include('core.ticket-office.inc.inscriptions')
                        </div>
                        <div class="col-xs-12">
                            @include('core.ticket-office.inc.packs')
                        </div>
                        @can('manage_gift_cards')
                        <div class="col-xs-12">
                            @include('core.ticket-office.inc.gift_cards')
                        </div>
                        @endcan
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row">
                        <div class="col-xs-12">
                            @include('core.ticket-office.inc.client')
                        </div>
                        <div class="col-xs-12">
                            @include('core.ticket-office.inc.payment')
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6">
                    <div class="row">
                        <div class="col-xs-12">
                            <input type="submit" class="btn btn-lg btn-success btn-confirm" value="{{ trans('tincket/backend.ticket.confirm_cart') }}"/>
                        </div>
                    </div>
                </div>                
            </div>
        </form>         
    </div>
</div>
@if($sessions->isNotEmpty())
@include('core.ticket-office.inc.loading')
@endif
@endsection 

@section('after_styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" rel="stylesheet" type="text/css" /> 
<!-- styles -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/zoomist@2/zoomist.css" />
<style>
    .zoomist-container {
        width: 100%;
    }

    .zoomist-wrapper{
        background: transparent;
    }

    .zoomist-image {
        /* width: 600px; */
        height: 100%;
        pointer-events: auto;
    }
</style>
@endsection 

@section('after_scripts') 
@parent()
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.8.3/underscore-min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/angular.js/1.5.8/angular.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/angular-ui-sortable/0.14.3/sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/zoomist@2/zoomist.umd.js"></script>
<script type="text/javascript">
    //Disable submitting form on enter pressed
    $("form").keypress(function(e) {
        //Enter key
        if (e.which == 13) {
            return false;
        }
    });

    $("form").submit(function() {
        $(".btn-confirm").attr("disabled", true);
        return true;
    });
    var slotStatus = {!! json_encode(\App\Status::where('id','!=',6)->get()->pluck('name', 'id')) !!}
</script>

<script src="https://cdn.jsdelivr.net/npm/@simonwep/selection-js/dist/selection.min.js"></script>
{{-- <script src="{{ asset('js/svg-pan-zoom-container.js') }}?v={{ time() }}"></script> --}}
{{-- In order to be able to push scripts from partials (inscriptions, client, etc.) --}} 
@stack('after_scripts') 
<script type="text/javascript" src="{{ asset('js/crud/ticket-office/angular-services.js')}}?v={{ time() }}"></script>
@endsection
