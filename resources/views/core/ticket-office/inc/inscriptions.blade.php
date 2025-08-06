<div class="box" ng-controller="InscriptionController" ng-init="data = JSON.parse('{!! json_encode($old_data ?? '{}') !!}');">
    <div class="box-header">
        <h3 class="box-title">{{ trans('ticket-office.inscriptions_set') }}</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>{{ trans('ticket-office.session') }}</th>
                    <th>{{ trans('ticket-office.rate') }}</th>
                    <th>{{ trans('ticket-office.slot') }}</th>
                    <th>{{ trans('ticket-office.price') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr ng-repeat="item in inscriptions">
                    <td style="width: 35%">
                        <% item.session.name %>
                        <input type="hidden" name="inscriptions[session_id][]" ng-value="item.session.id" />
                    </td>
                    <td style="width: 25%">
                        <input type="hidden" name="inscriptions[rate_id][]" ng-value="item.selected_rate.id" />
                        <select class="form-select form-select-sm session select-rates"
                            ng-options="rate.name.{{ config('app.locale') }} for rate in item.slot.rates track by rate.id"
                            ng-model="item.selected_rate" ng-change="updateRate(item)"></select>
                    </td>
                    <td style="width: 25%">
                        <p class="mb-0"><% item.slot.name %></p>
                        <input type="hidden" name="inscriptions[slot_id][]" ng-value="item.slot.id" />
                    </td>
                    <td style="width: 10%">
                        <p class="mb-0"><% item.price %>€</p>
                    </td>
                    <td style="width: 5%">
                        <button class="btn btn-sm btn-outline-danger" type="button" ng-click="removeItem($index);">
                            <i class="fas fa-trash" aria-hidden="true"></i>
                            <span class="visually-hidden">{{ trans('ticket-office.deleteitem') }}</span>
                        </button>
                    </td>
                </tr>
                <tr>
                    <td colspan="5" class="text-end">
                        <button class="btn btn-sm btn-success" type="button" data-bs-toggle="modal" data-bs-target="#layoutModal">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                            <span class="visually-hidden">{{ trans('ticket-office.add_inscription') }}</span>
                        </button>
                    </td>
                </tr>
                <tr class="table-secondary">
                    <td>
                        <strong>{{ trans('ticket-office.total') }}</strong>
                    </td>
                    <td colspan="2"></td>
                    <td>
                        <strong><% getTotal() %>€</strong>
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- LAYOUT MODAL FOR INSCRIPTION SELLING -->
<div id="layoutModal" class="modal fade" tabindex="-1" role="dialog" ng-controller="LayoutController">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">{{ trans('ticket-office.select_session') }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <select class="form-select session" ng-model="current_session"
                            ng-options="item as item.name for item in sessions track by item.id"
                            ng-change="updateLayout()"></select>
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
                            <i class="fas fa-info-circle"></i>
                            {{ trans('ticket-office.tickets_sold') }}
                            <strong><% current_session.sold %></strong>
                        </div>
                    </div>
                    <div class="col-md-6" ng-show="current_session.free_positions < 30">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            {{ trans('ticket-office.there_is_only') }}<strong><% current_session.free_positions %></strong>{{ trans('ticket-office.free_slots_in_session') }}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-9">
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
                            <div class="card mb-2" ng-repeat="rate in current_session.rates">
                                <div class="card-body">
                                    <label class="form-label"><% rate.name.{{ config('app.locale') }} %></label>
                                    <div class="wrapper-rate">
                                        <button type="button" class="minus" ng-click="rate.quantity = rate.quantity > 0 ? rate.quantity - 1 : 0; removeNonNumberedInscription(rate);">-</button>
                                        <span class="num" ng-bind="{{ $calculeFreePositions ? 'rate.quantity + \'/\' + rate.available' : 'rate.quantity' }}"></span>
                                        <button type="button" class="plus" ng-click="rate.quantity = rate.quantity + 1; addNonNumberedInscription(rate);">+</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div ng-show="current_session.free_positions < 30" class="row">
                                <div class="col-md-6 offset-md-6">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        {{ trans('ticket-office.there_is_only') }}<strong><% current_session.free_positions %></strong>{{ trans('ticket-office.free_slots_in_session') }}
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
                                    <small><% inscription.slot.name %></small>
                                </div>
                                <div ng-if="inscription.slot.comment" class="mt-1">
                                    <small class="text-muted"><strong><% inscription.slot.comment %></strong></small>
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
                            {{ trans('ticket-office.add') }} <i class="fas fa-cart-plus"></i>
                        </button>
                        <button type="button" class="btn btn-info" id="to-remove">
                            {{ trans('ticket-office.remove') }}
                        </button>
                    </div>
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                        {{ trans('ticket-office.end_selection') }}
                    </button>
                </div>
                <div ng-show="current_session.is_numbered" class="w-100">
                    <hr>
                    @include('core.ticket-office.inc.space_layout_legend')
                    <p class="help-text text-start small">{!! trans('ticket-office.svg_layout.help') !!}</p>
                </div>
            </div>
        </div>
    </div>
</div>

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