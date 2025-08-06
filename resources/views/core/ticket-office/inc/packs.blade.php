<div class="box">
    <div class="box-header">
        <h3 class="box-title">{{ trans('ticket-office.packs') }}</h3>
    </div>
    <div class="box-body table-responsive" ng-controller="PackController">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>{{ trans('ticket-office.pack') }}</th>
                    <th>{{ trans('ticket-office.inscriptions') }}</th>
                    <th>{{ trans('ticket-office.price') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr ng-repeat="pack in packService.packs">
                    <td>
                        <% pack.name.{{config('app.locale')}} %>
                    </td>
                    <td>
                        <input type="hidden" name="packs[]" ng-value="prepareJson(pack)" />
                        <div ng-repeat="inscription in pack.inscriptions" class="small">
                            <% inscription.session.event.name.{{config('app.locale')}} %>
                            <span ng-if="inscription.session.is_numbered"> -
                                <% inscription.slot.name %>
                            </span>
                        </div>
                    </td>
                    <td>
                        <% pack.price %>€
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-danger" type="button" ng-click="removeItem($index);">
                            <i class="fas fa-trash" aria-hidden="true"></i>
                            <span class="visually-hidden">{{ trans('ticket-office.delete_item') }}</span>
                        </button>
                    </td>
                </tr>
                <tr>
                    <td colspan="4" class="text-end">
                        <button class="btn btn-sm btn-success" type="button" data-bs-toggle="modal" data-bs-target="#packsModal">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                            <span class="visually-hidden">{{ trans('ticket-office.new_pack') }}</span>
                        </button>
                    </td>
                </tr>
                <tr class="table-secondary">
                    <td>
                        <strong>{{ trans('ticket-office.total') }}</strong>
                    </td>
                    <td></td>
                    <td>
                        <strong><% getTotal() %>€</strong>
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- LAYOUT MODAL FOR PACKS SELLING -->
<div id="packsModal" class="modal fade" tabindex="-1" role="dialog" ng-controller="PackConfiguratorController">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" ng-show="step == 0">{{ trans('ticket-office.select_the_pack') }}</h4>
                <h4 class="modal-title" ng-show="step == 1">{{ trans('ticket-office.select_the_sessions') }}</h4>
                <h4 class="modal-title" ng-show="step == 2">{{ trans('ticket-office.select_slots_for') }} <strong><% current_session.event.name.{{ config('app.locale')}} %></strong></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" ng-show="step == 0">
                <div class="row mb-4">
                    <div class="col-auto">
                        <a class="btn btn-outline-secondary" href="{{ Request::url().'?'.http_build_query(['show_expired' => true]) }}">
                            {{ trans('ticket-office.show_all_packs')}}
                        </a>
                    </div>
                </div>
                <div class="row">
                    @foreach($packs as $pack)
                    @php
                        // remove description to prevent broke js json if containts ; or ""
                        $pack->description = null;
                    @endphp
                    
                    <div class="col-md-4 mb-3" ng-click="selectPack({{ $pack }}); step = step + 1;">
                        <div class="card h-100" style="cursor: pointer;">
                            <div class="card-body">
                                <h5 class="card-title">{{ $pack->name }}</h5>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-body" ng-show="step == 1">
                <div class="alert alert-info" ng-show="min_session_selection > 0 && !all_sessions">
                    <i class="fas fa-info-circle"></i> {{ trans('ticket-office.select_at_least') }} <strong><% min_session_selection %></strong> {{ trans('ticket-office.sessions_to_sell_this_pack') }}.
                </div>
                <div class="row">
                    <div ng-repeat="event in events">
                        <div class="col-md-4 mb-3" ng-repeat="session in event.sessions">
                            <div class="card" ng-class="{ 'border-success bg-success bg-opacity-10': session.is_selected }" ng-click="toggleSession(session);" style="cursor: pointer;">
                                <div class="card-body">
                                    <% event.name.{{ config('app.locale')}} %><span ng-if="session.name.{{ config('app.locale')}}"> - <% session.name.{{ config('app.locale')}} %></span> (<% session.starts_on %>)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-body" ng-if="step == 2">
                <div class="row">
                    <div class="col-1 d-flex align-items-center">
                        <button class="btn btn-link" ng-click="previousSession()" ng-if="current_session_index > 0;">
                            <i class="fas fa-chevron-left text-muted"></i>
                        </button>
                    </div>
                    <div class="col-7">
                        <!-- zoomist-container -->
                        <div class="zoomist-container-pack" ng-if="current_session.is_numbered">
                            <!-- zoomist-wrapper is required -->
                            <div class="zoomist-wrapper">
                                <!-- zoomist-image is required -->
                                <div class="zoomist-image">
                                    <space-layout layout-url="layout" layout-session="current_session"
                                        on-add-inscription="addInscription" on-remove-inscription="removeInscription"
                                        inscription-service="inscriptionService" pack-service="packService"
                                        gift-service="giftService" type-model="pack">
                                    </space-layout>
                                </div>
                            </div>
                        </div>

                        <div ng-if="!current_session.is_numbered" class="row">
                            <div class="col-md-6 offset-md-3">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> {{ trans('ticket-office.sessio_no_numerada') }}<br>
                                    <div ng-if="current_session.free_positions < 30">
                                        {{ trans('ticket-office.there_is_only') }}<strong><% current_session.free_positions %></strong>{{ trans('ticket-office.free_slots_in_session') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-1 d-flex align-items-center">
                        <button class="btn btn-link" ng-click="nextSession();" ng-if="packService.getSelectedSessions()[current_session_index + 1]">
                            <i class="fas fa-chevron-right text-muted"></i>
                        </button>
                    </div>
                    <div class="col-3">
                        <div ng-repeat="session in packService.getSelectedSessions()" class="mb-3">
                            <div class="card" ng-class="{active: $index == current_session_index}">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small><% session.event.name.{{ config('app.locale')}} %></small>
                                        <span class="badge bg-primary"><% session.is_numbered ? session.selection.length : pack_multiplier %>/<% pack_multiplier %></span>
                                    </div>
                                    <div ng-if="session.is_numbered && !session.selection.length" class="mt-1">
                                        <span class="badge bg-danger">{{ trans('ticket-office.pendent') }}</span>
                                    </div>
                                    <div ng-if="!session.is_numbered" class="mt-1">
                                        <span class="badge bg-success">{{ trans('ticket-office.sessio_no_numerada') }}</span>
                                    </div>
                                    <div ng-repeat="inscription in session.selection" class="mt-1">
                                        <small class="text-muted"><% inscription.slot.name %></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-secondary" ng-click="reset()">
                    {{ trans('ticket-office.reset') }}
                </button>
                <div class="d-flex align-items-center gap-3">
                    <div ng-show="step == 1" class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0">{{ trans('ticket-office.how_many_packs') }}?</label>
                        <select class="form-select form-select-sm" ng-model="pack_multiplier" style="width: auto;">
                            <option ng-repeat="number in options" ng-selected="pack_multiplier == 1"><% number %></option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" ng-disabled="!isNextStepReady()" ng-click="nextStep();">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('after_scripts')
<script type="text/javascript" src="{{ asset('js/crud/ticket-office/angular-create-packs.js')}}?v={{ time() }}"></script>
@endpush