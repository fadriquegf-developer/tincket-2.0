<div class="box" ng-controller="InscriptionController" ng-init="data = {{ json_encode($old_data ?? []) }};">
    <div class="box-header">
        <h3 class="box-title">{{ trans('ticket-office.inscriptions_set') }}</h3>
    </div>
    <div class="box-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th style="width: 35%">{{ trans('ticket-office.session') }}</th>
                        <th style="width: 25%">{{ trans('ticket-office.rate') }}</th>
                        <th style="width: 20%">{{ trans('ticket-office.slot') }}</th>
                        <th style="width: 15%">{{ trans('ticket-office.price') }}</th>
                        <th style="width: 5%"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr ng-repeat="item in inscriptions">
                        <td>
                            <% item.session.name %>
                            <input type="hidden" name="inscriptions[session_id][]" ng-value="item.session.id" />
                        </td>
                        <td>
                            <input type="hidden" name="inscriptions[rate_id][]" ng-value="item.selected_rate.id" />
                            <select class="form-select form-select-sm"
                                ng-options="rate.name['{{ config('app.locale') }}'] for rate in item.slot.rates track by rate.id"
                                ng-model="item.selected_rate" 
                                ng-change="updateRate(item)">
                            </select>
                        </td>
                        <td>
                            <span class="text-muted"><% item.slot.name %></span>
                            <input type="hidden" name="inscriptions[slot_id][]" ng-value="item.slot.id" />
                        </td>
                        <td>
                            <strong><% item.price %>€</strong>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger" type="button" ng-click="removeItem($index);">
                                <i class="la la-trash" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5" class="text-end">
                            <button class="btn btn-sm btn-success" type="button" data-bs-toggle="modal" data-bs-target="#layoutModal">
                                <i class="la la-plus" aria-hidden="true"></i>
                                {{ trans('ticket-office.add_inscription') }}
                            </button>
                        </td>
                    </tr>
                    <tr class="table-secondary">
                        <td colspan="3">
                            <strong>{{ trans('ticket-office.total') }}</strong>
                        </td>
                        <td>
                            <strong class="text-success"><% getTotal() %>€</strong>
                        </td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL FOR INSCRIPTION SELLING -->
<div id="layoutModal" class="modal fade" tabindex="-1" ng-controller="LayoutController">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ trans('ticket-office.select_session') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <select class="form-select" ng-model="current_session"
                            ng-options="item as item.name for item in sessions track by item.id"
                            ng-change="updateLayout()">
                        </select>
                    </div>
                    <div class="col-md-4">
                        <a class="btn btn-outline-secondary" 
                           href="{{ Request::url() . '?' . http_build_query(['show_expired' => true]) }}">
                            {{ trans('ticket-office.show_all_sessions') }}
                        </a>
                    </div>
                </div>

                <div class="row mb-3" ng-show="current_session.is_numbered">
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <i class="la la-info-circle"></i>
                            {{ trans('ticket-office.tickets_sold') }}:
                            <strong><% current_session.sold %></strong>
                        </div>
                    </div>
                    <div class="col-md-6" ng-show="current_session.free_positions < 30">
                        <div class="alert alert-warning">
                            <i class="la la-exclamation-triangle"></i>
                            {{ trans('ticket-office.there_is_only') }} 
                            <strong><% current_session.free_positions %></strong>
                            {{ trans('ticket-office.free_slots_in_session') }}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-9">
                        <!-- Zoomist container for numbered sessions -->
                        <div class="zoomist-container-inscription" ng-show="current_session.is_numbered">
                            <div class="zoomist-wrapper">
                                <div class="zoomist-image">
                                    <space-layout layout-url="layout"
                                        layout-session="current_session" 
                                        on-add-inscription="addInscription"
                                        on-remove-inscription="removeInscription"
                                        inscription-service="inscriptionService" 
                                        pack-service="packService"
                                        gift-service="giftService" 
                                        type-model="inscription">
                                    </space-layout>
                                </div>
                            </div>
                        </div>

                        <!-- Non-numbered sessions -->
                        <div ng-show="!current_session.is_numbered">
                            <div class="card mb-2" ng-repeat="rate in current_session.rates">
                                <div class="card-body">
                                    <label class="form-label"><% rate.name['{{ config('app.locale') }}'] %></label>
                                    <div class="wrapper-rate">
                                        <button type="button" class="minus" 
                                                ng-click="rate.quantity = rate.quantity > 0 ? rate.quantity - 1 : 0; removeNonNumberedInscription(rate);">
                                            <i class="la la-minus"></i>
                                        </button>
                                        <span class="num">
                                            <% rate.quantity %> / <% rate.available %>
                                        </span>
                                        <button type="button" class="plus" 
                                                ng-click="rate.quantity = rate.quantity + 1; addNonNumberedInscription(rate);">
                                            <i class="la la-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div ng-show="current_session.free_positions < 30" class="row">
                                <div class="col-md-6 offset-md-3">
                                    <div class="alert alert-warning">
                                        <i class="la la-exclamation-triangle"></i>
                                        {{ trans('ticket-office.there_is_only') }}
                                        <strong><% current_session.free_positions %></strong>
                                        {{ trans('ticket-office.free_slots_in_session') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="list-group">
                            <div ng-repeat="inscription in inscriptions" class="list-group-item">
                                <strong><% inscription.session.name %></strong>
                                <div ng-show="inscription.slot.name">
                                    <small class="text-muted"><% inscription.slot.name %></small>
                                </div>
                                <div ng-if="inscription.slot.comment" class="mt-1">
                                    <small class="text-info"><% inscription.slot.comment %></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div ng-show="current_session.is_numbered">
                        <button type="button" class="btn btn-warning me-2" id="to-add">
                            {{ trans('ticket-office.add') }} <i class="la la-cart-plus"></i>
                        </button>
                        <button type="button" class="btn btn-info" id="to-remove">
                            {{ trans('ticket-office.remove') }}
                        </button>
                    </div>
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                        {{ trans('ticket-office.end_selection') }}
                    </button>
                </div>
                <div ng-show="current_session.is_numbered" class="w-100 mt-3">
                    <hr>
                    @include('core.ticket-office.inc.space_layout_legend')
                    <p class="help-text text-start small text-muted">{!! trans('ticket-office.svg_layout.help') !!}</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('after_scripts')
<script>
$(function() {
    // Selection multiple buttons
    $('#to-add').on('click', function() {
        $('.slot.selected').removeClass('selected').each(function() {
            if (!$(this).prop('checked')) {
                $(this).trigger('slotClick');
            }
        });
    });
    $('#to-remove').on('click', function() {
        $('.slot.selected').removeClass('selected').each(function() {
            if ($(this).prop('checked')) {
                $(this).trigger('slotClick');
            }
        });
    });
});
</script>
@endpush
