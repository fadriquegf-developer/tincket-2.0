<div class="box" ng-controller="GiftController">
    <div class="box-header">
        <h3 class="box-title">{{ trans('tincket/backend.gift_card.gift_cards') }}</h3>
        <div class="box-tools">
            <div class="input-group input-group-sm" style="width: 150px;">
                <input name="table_search" ng-model="code" class="form-control pull-right" placeholder="Code"
                    type="text">
                <div class="input-group-btn">
                    <button class="btn btn-default alert-success" type="button"
                        ng-click="validate()">{{ trans('tincket/backend.gift_card.validate') }}</button>
                </div>
            </div>
        </div>
    </div>
    <div class="box-body table-responsive no-padding">

        <table class="table table-hover">
            <thead>
                <tr>
                    <th>{{ trans('tincket/backend.ticket.session') }}</th>
                    <th>{{ trans('tincket/backend.ticket.slot') }}</th>
                    <th>{{ trans('tincket/backend.gift_card.code') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr ng-repeat="item in inscriptions">
                    <td width="50%">
                        <% item.session.event.name.{{ config('app.locale') }} %>
                        <span ng-if="item.session.name.{{ config('app.locale') }}">-
                            <% item.session.name.{{ config('app.locale') }} %></span> (<% item.session.starts_on %>)
                        <input type="hidden" name="gift_cards[session_id][]" ng-value="item.session.id" />
                    </td>
                    <td width="30%">
                        <p><% item.slot.name %></p>
                        <input type="hidden" name="gift_cards[slot_id][]" ng-value="item.slot.id" />
                    </td>
                    <td width="30%">
                        <p><% item.code %></p>
                        <input type="hidden" name="gift_cards[code][]" ng-value="item.code" />
                    </td>
                    <td>
                        <button class="btn btn-sm btn-default" type="button" ng-click="removeItem($index);"><span
                                class="sr-only">{{ trans('tincket/backend.deleteitem') }}</span><i class="fa fa-trash"
                                role="presentation" aria-hidden="true"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- LAYOUT MODAL FOR INSCRIPTION SELLING -->
<div id="layoutGiftModal" class="modal fade" tabindex="-1" role="dialog" ng-controller="GiftConfiguratorController">
    <div class="modal-dialog modal-lg" role="document" style="width: 95%;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 ng-show="step == 0">{{ trans('tincket/backend.gift_card.select_the_sessions') }}</h4>
                <h4 ng-show="step == 1">{{ trans('tincket/backend.ticket.select_slots_for') }}
                    <strong><% current_session.event.name.{{ config('app.locale') }} %></strong>
                </h4>
            </div>
            <div class="modal-body" ng-show="step == 0">
                <div class="row">
                    <div class="col-xs-4" ng-repeat="session in event.next_sessions">
                        <div class="panel panel-default alert" ng-class="{ 'alert-success': session.is_selected }"
                            ng-click="toggleSession(session);">
                            <div class="">
                                <% event.name.{{ config('app.locale') }} %><span
                                    ng-if="session.name.{{ config('app.locale') }}"> -
                                    <% session.name.{{ config('app.locale') }} %></span> (<% session.starts_on %>)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-body" ng-if="step == 1">
                <div class="row row-packs">
                    <div class="col-xs-1">
                        <a class="left carousel-control" style="width: 100%;" role="button" data-slide="prev"
                            ng-click="previousSession()" ng-if="current_session_index > 0;">
                            <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"
                                style="color: grey;"></span>
                        </a>
                    </div>
                    <div class="col-xs-7">
                        <space-layout layout-url="layout" layout-session="current_session"
                            on-add-inscription="addInscription" on-remove-inscription="removeInscription"
                            inscription-service="inscriptionService" pack-service="packService"
                            gift-service="giftService" type-model="pack"
                            ng-if="current_session.is_numbered"></space-layout>
                        <div ng-if="!current_session.is_numbered" class="row modal-body">
                            <div class="col-xs-6 col-sm-offset-3">
                                <div class="alert alert-info">
                                    <span class="glyphicon glyphicon-info-sign"></span>
                                    {{ trans('tincket/backend.ticket.sessio_no_numerada') }}<br>
                                    <div ng-if="current_session.free_positions < 30">
                                        {{ trans('tincket/backend.ticket.there_is_only') }}<strong><% current_session.free_positions %></strong>{{ trans('tincket/backend.ticket.free_slots_in_session') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">

                    </div>
                </div>
            </div>
            <div class="modal-footer form-inline">
                <button type="button" class="btn"
                    ng-click="reset()">{{ trans('tincket/backend.ticket.reset') }}</button>

                <button type="button" class="btn btn-primary" ng-disabled="!isNextStepReady()"
                    ng-click="nextStep();">Next</button>
            </div>
        </div>
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
