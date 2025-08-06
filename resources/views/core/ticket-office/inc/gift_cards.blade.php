<div class="box" ng-controller="GiftController">
    <div class="box-header">
        <h3 class="box-title">{{ trans('ticket-office.gift_cards') }}</h3>
        <div class="box-tools">
            <div class="input-group input-group-sm" style="width: 250px;">
                <input name="table_search" ng-model="code" class="form-control" placeholder="Code" type="text">
                <button class="btn btn-success" type="button" ng-click="validate()">
                    {{ trans('ticket-office.gift_card.validate') }}
                </button>
            </div>
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>{{ trans('ticket-office.session') }}</th>
                    <th>{{ trans('ticket-office.slot') }}</th>
                    <th>{{ trans('ticket-office.gift_card.code') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr ng-repeat="item in inscriptions">
                    <td style="width: 50%">
                        <% item.session.event.name.{{ config('app.locale') }} %>
                        <span ng-if="item.session.name.{{ config('app.locale') }}">-
                            <% item.session.name.{{ config('app.locale') }} %></span> (<% item.session.starts_on %>)
                        <input type="hidden" name="gift_cards[session_id][]" ng-value="item.session.id" />
                    </td>
                    <td style="width: 25%">
                        <p class="mb-0"><% item.slot.name %></p>
                        <input type="hidden" name="gift_cards[slot_id][]" ng-value="item.slot.id" />
                    </td>
                    <td style="width: 20%">
                        <p class="mb-0"><% item.code %></p>
                        <input type="hidden" name="gift_cards[code][]" ng-value="item.code" />
                    </td>
                    <td style="width: 5%">
                        <button class="btn btn-sm btn-outline-danger" type="button" ng-click="removeItem($index);">
                            <i class="fas fa-trash" aria-hidden="true"></i>
                            <span class="visually-hidden">{{ trans('ticket-office.deleteitem') }}</span>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- LAYOUT MODAL FOR GIFT CARD SELLING -->
<div id="layoutGiftModal" class="modal fade" tabindex="-1" role="dialog" ng-controller="GiftConfiguratorController">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" ng-show="step == 0">{{ trans('ticket-office.gift_card.select_the_sessions') }}</h4>
                <h4 class="modal-title" ng-show="step == 1">{{ trans('ticket-office.select_slots_for') }}
                    <strong><% current_session.event.name.{{ config('app.locale') }} %></strong>
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" ng-show="step == 0">
                <div class="row">
                    <div class="col-md-4 mb-3" ng-repeat="session in event.next_sessions">
                        <div class="card" ng-class="{ 'border-success bg-success bg-opacity-10': session.is_selected }"
                            ng-click="toggleSession(session);" style="cursor: pointer;">
                            <div class="card-body">
                                <% event.name.{{ config('app.locale') }} %><span
                                    ng-if="session.name.{{ config('app.locale') }}"> -
                                    <% session.name.{{ config('app.locale') }} %></span> (<% session.starts_on %>)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-body" ng-if="step == 1">
                <div class="row">
                    <div class="col-1 d-flex align-items-center">
                        <button class="btn btn-link" ng-click="previousSession()" ng-if="current_session_index > 0;">
                            <i class="fas fa-chevron-left text-muted"></i>
                        </button>
                    </div>
                    <div class="col-7">
                        <div class="zoomist-container-pack" ng-if="current_session.is_numbered">
                            <div class="zoomist-wrapper">
                                <div class="zoomist-image">
                                    <space-layout layout-url="layout" layout-session="current_session"
                                        on-add-inscription="addInscription" on-remove-inscription="removeInscription"
                                        inscription-service="inscriptionService" pack-service="packService"
                                        gift-service="giftService" type-model="pack"></space-layout>
                                </div>
                            </div>
                        </div>
                        <div ng-if="!current_session.is_numbered" class="row">
                            <div class="col-md-6 offset-md-3">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    {{ trans('ticket-office.sessio_no_numerada') }}<br>
                                    <div ng-if="current_session.free_positions < 30">
                                        {{ trans('ticket-office.there_is_only') }}<strong><% current_session.free_positions %></strong>{{ trans('ticket-office.free_slots_in_session') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <!-- Espacio para información adicional si es necesario -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" ng-click="reset()">
                    {{ trans('ticket-office.reset') }}
                </button>
                <button type="button" class="btn btn-primary" ng-disabled="!isNextStepReady()" ng-click="nextStep();">
                    Next
                </button>
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