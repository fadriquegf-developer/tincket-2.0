@extends('backpack::layout')
                         
@section('header')
<section class="content-header">
    <h1>
        Tickets office
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ url(config('backpack.base.route_prefix'), 'dashboard')}}">{{ trans('backpack::crud.admin')}}</a></li>
        <li><a href="{{ route('ticket-office.create')}}" class="text-capitalize">{{ trans('tincket/backend.ticket.tickets_office') }}</a></li>
        <li class="active">Create</li>
    </ol>
</section>
@endsection
@section('content')
@include('core.ticket-office.inc.form_old_values')
<?php $old_data = $this->old_data ?? []; ?>

@include('core.ticket-office.inc.form_errors')
<div class="row">
    <div @include('crud::inc.field_wrapper_attributes')>
         <form action="{{ route('ticket-office.store')}}" method="POST">
            {{ csrf_field() }}
            <div class="row">
                <div class="col-lg-6">
                    <div class="row">
                        <div class="col-xs-12">
                            @include('core.ticket-office.inc.unconfirmed-carts')
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row">
                        <div class="col-xs-12">
                            @include('core.ticket-office.inc.client')
                        </div>
                        <div class="col-xs-12">                            
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-6">
                    <div class="row">
                        <div class="col-xs-12">
                            <input type="submit" class="btn btn-lg btn-success" value="{{ trans('tincket/backend.ticket.confirm_cart') }}"/>
                        </div>
                    </div>
                </div>                
            </div>
        </form>                 
    </div>
</div>
@endsection 

@section('after_styles')
<link href="http://admin.client.dev/vendor/backpack/select2/select2.css" rel="stylesheet" type="text/css" />
<link href="http://admin.client.dev/vendor/backpack/select2/select2-bootstrap-dick.css" rel="stylesheet" type="text/css" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" rel="stylesheet" type="text/css" /> @endsection @section('after_scripts') @parent()
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.8.3/underscore-min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/angular.js/1.5.8/angular.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/angular-ui-sortable/0.14.3/sortable.min.js"></script>

<script type="text/javascript" src="{{ asset('js/crud/ticket-office/create.js')}}?v={{ time() }}"></script>

<script type="text/javascript">
    $.ajaxPrefilter(function(options, originalOptions, xhr) {
          var token = $('meta[name="csrf_token"]').attr('content');

          if (token) {
                return xhr.setRequestHeader('X-XSRF-TOKEN', token);
          }
      });

      // make the delete button work in the first result page
      register_delete_button_action();

      // make the delete button work on subsequent result pages
      $('#crudTable').on( 'draw.dt',   function () {
         register_delete_button_action();
      } ).dataTable();

      function register_delete_button_action() {
        $("[data-button-type=delete]").unbind('click');
        // CRUD Delete
        // ask for confirmation before deleting an item
        $("[data-button-type=delete]").click(function(e) {
          e.preventDefault();
          var delete_button = $(this);
          var delete_url = $(this).attr('href');

          if (confirm("{{ trans('backpack::crud.delete_confirm') }}") == true) {
              $.ajax({
                  url: delete_url,
                  type: 'DELETE',
                  success: function(result) {
                      // Show an alert with the result
                      new PNotify({
                          title: "{{ trans('backpack::crud.delete_confirmation_title') }}",
                          text: "{{ trans('backpack::crud.delete_confirmation_message') }}",
                          type: "success"
                      });
                      // delete the row from the table
                      delete_button.parentsUntil('tr').parent().remove();
                  },
                  error: function(result) {
                      // Show an alert with the result
                      new PNotify({
                          title: "{{ trans('backpack::crud.delete_confirmation_not_title') }}",
                          text: "{{ trans('backpack::crud.delete_confirmation_not_message') }}",
                          type: "warning"
                      });
                  }
              });
          } else {
              new PNotify({
                  title: "{{ trans('backpack::crud.delete_confirmation_not_deleted_title') }}",
                  text: "{{ trans('backpack::crud.delete_confirmation_not_deleted_message') }}",
                  type: "info"
              });
          }
        });
      }
</script>

{{-- In order to be able to push scripts from partials (inscriptions, client, etc.) --}} 
@stack('after_scripts') 
@endsection
