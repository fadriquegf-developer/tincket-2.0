<div class="box" ng-controller="InscriptionController" ng-init="data = JSON.parse('{!! json_encode($old_data ?? '{}') !!}');">
    <div class="box-header">
        <h3 class="box-title">{{ trans('tincket/backend.ticket.inscriptions_set') }}</h3>
        <div class="box-tools">
            {{-- <div class="input-group input-group-sm" style="width: 150px;">
                <input name="table_search" class="form-control pull-right" placeholder="Search" type="text">
                <div class="input-group-btn">
                    <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                </div>
            </div> --}}
        </div>
    </div>
    <div class="box-body table-responsive no-padding">

        <table class="table table-hover">
            <thead>
                <tr>
                    <th>{{ trans('tincket/backend.ticket.session') }}</th>
                    <th>{{ trans('tincket/backend.ticket.rate') }}</th>
                    <th>{{ trans('tincket/backend.ticket.slot') }}</th>
                    <th>{{ trans('tincket/backend.ticket.price') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr ng-repeat="item in inscriptions">
                    <td width="50%">
                        <% item.session.name %>
                        <input type="hidden" name="inscriptions[session_id][]" ng-value="item.session.id" />
                    </td>
                    <td width="30%">
                        <input type="hidden" name="inscriptions[rate_id][]" ng-value="item.selected_rate.id" />
                        <select class="form-control session select-rates"
                            ng-options="rate.name.{{ config('app.locale') }} for rate in item.slot.rates track by rate.id"
                            ng-model="item.selected_rate" ng-change="updateRate(item)"></select>
                    </td>
                    <td width="30%">
                        <p><% item.slot.name %></p>
                        <input type="hidden" name="inscriptions[slot_id][]" ng-value="item.slot.id" />
                    </td>
                    <td>
                        <p><% item.price %>€</p>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-default" type="button" ng-click="removeItem($index);"><span
                                class="sr-only">{{ trans('tincket/backend.deleteitem') }}</span><i class="fa fa-trash"
                                role="presentation" aria-hidden="true"></i></button>
                    </td>
                </tr>
                <tr>
                    <td colspan="99">
                        <button class="btn btn-sm btn-default pull-right alert-success" type="button"
                            data-toggle="modal" data-target="#layoutModal"><span
                                class="sr-only">{{ trans('tincket/backend.ticket.add_inscription') }}</span><i
                                class="fa fa-plus" role="presentation" aria-hidden="true"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>{{ trans('tincket/backend.ticket.total') }}</b>
                    </td>
                    <td colspan="2">
                    </td>
                    <td>
                        <p><% getTotal() %>€</p>
                    </td>
                    <td>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<!-- LAYOUT MODAL FOR INSCRIPTION SELLING -->
<div id="layoutModal" class="modal fade" tabindex="-1" role="dialog" ng-controller="LayoutController">
    <div class="modal-dialog modal-lg" role="document" style="width: 95%;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="col-xs-12">{{ trans('tincket/backend.ticket.select_session') }}</h2>
                <div class="col-xs-6 col-md-10">
                    <select class="form-control session" ng-model="current_session"
                        ng-options="item as item.name for item in sessions track by item.id"
                        ng-change="updateLayout()"></select>
                </div>
                <div class="col-xs-6 col-md-2">
                    <a class="btn btn-default"
                        href="{{ Request::url() . '?' . http_build_query(['show_expired' => true]) }}">{{ trans('tincket/backend.ticket.show_all_sessions') }}</a>
                </div>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div ng-show="current_session.is_numbered">
                        <div class="col-xs-6">
                            <div class="alert alert-info">
                                <span class="glyphicon glyphicon-info-sign"></span>
                                {{ trans('tincket/backend.ticket.tickets_sold') }}
                                <strong><% current_session.sold %></strong>
                            </div>
                        </div>
                        <div class="col-xs-6" ng-show="current_session.free_positions < 30">
                            <div class="alert alert-warning">
                                <span class="glyphicon glyphicon-info-sign"></span>
                                {{ trans('tincket/backend.ticket.there_is_only') }}<strong><% current_session.free_positions %></strong>{{ trans('tincket/backend.ticket.free_slots_in_session') }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-9">
                        <!-- zoomist-container -->
                        <div class="zoomist-container-inscription">
                            <!-- zoomist-wrapper is required -->
                            <div class="zoomist-wrapper">
                                <!-- zoomist-image is required -->
                                <div class="zoomist-image">
                                    <space-layout ng-show="current_session.is_numbered" layout-url="layout"
                                        layout-session="current_session" on-add-inscription="addInscription"
                                        on-remove-inscription="removeInscription"
                                        inscription-service="inscriptionService" pack-service="packService"
                                        gift-service="giftService" type-model="inscription"></space-layout>
                                </div>
                            </div>
                        </div>

                        <div ng-show="!current_session.is_numbered">
                            <div class="list-group-item" ng-repeat="rate in current_session.rates">
                                <label><% rate.name.{{ config('app.locale') }} %></label>
                                <div class="wrapper-rate">
                                    <span class="minus" ng-click="rate.quantity = rate.quantity > 0 ? rate.quantity - 1 : 0; removeNonNumberedInscription(rate);">-</span>
                                    <span class="num" ng-bind="{{ $calculeFreePositions ? 'rate.quantity + \'/\' + rate.available' : 'rate.quantity' }}"></span>
                                    <span class="plus" ng-click="rate.quantity = rate.quantity + 1; addNonNumberedInscription(rate);">+</span>
                                </div>
                            </div>
                            
                            <div ng-show="current_session.free_positions < 30" class="row modal-body">
                                <div class="col-xs-6 col-xs-offset-6">
                                    <div class="alert alert-warning">
                                        <span class="glyphicon glyphicon-info-sign"></span>
                                        {{ trans('tincket/backend.ticket.there_is_only') }}<strong><% current_session.free_positions %></strong>{{ trans('tincket/backend.ticket.free_slots_in_session') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <ul class="list-group">
                            <li ng-repeat="inscription in inscriptions" class="list-group-item">
                                <% inscription.session.name %>
                                <span ng-show="inscription.slot.name"> - <% inscription.slot.name %><span>
                                        <span
                                            ng-if="inscription.slot.comment"><br /><strong><% inscription.slot.comment %></strong>
                                        </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="row">
                    <div class="col-xs-6 text-left" ng-show="current_session.is_numbered">
                        <button type="button" class="btn btn-warning"
                            id="to-add">{{ trans('tincket/backend.ticket.add') }} <i class="fa  fa-cart-plus"></i>
                        </button>
                        <button type="button" class="btn btn-info"
                            id="to-remove">{{ trans('tincket/backend.ticket.remove') }}</button>
                    </div>
                    <div class="col-xs-6">
                        <button type="button" class="btn btn-success"
                            data-dismiss="modal">{{ trans('tincket/backend.ticket.end_selection') }}</button>
                    </div>
                </div>
                <div ng-show="current_session.is_numbered">
                    <hr>

                    @include('core.ticket-office.inc.space_layout_legend')

                    <p class="help-block text-left">{!! trans('tincket/backend.svg_layout.help') !!}</p>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<!-- END MLAYOUT MODAL -->
@push('after_scripts')
    <script type="text/javascript">
        // ugly way to pass data to angular service
        @if ($json_sessions->count() > 0)
            window.sessions_list = @json($json_sessions);
        @else
            window.sessions_list = @json([0 => ['space' => ['layout' => '']]]);
        @endif
    </script>

    <script type="text/javascript" src="{{ asset('js/crud/ticket-office/angular-create-inscriptions.js') }}?v={{ time() }}"></script>

    <script>
        $(function() {
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
