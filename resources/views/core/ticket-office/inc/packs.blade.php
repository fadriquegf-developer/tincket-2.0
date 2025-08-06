<div class="box">
    <div class="box-header">
        <h3 class="box-title">{{ trans('tincket/backend.ticket.packs') }}</h3>
    </div>
    <div class="box-body table-responsive no-padding" ng-controller="PackController">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>{{ trans('tincket/backend.ticket.pack') }}</th>
                    <th>{{ trans('tincket/backend.ticket.inscriptions') }}</th>
                    <th>{{ trans('tincket/backend.ticket.price') }}</th>
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
                        <span ng-repeat="inscription in pack.inscriptions">
                            <% inscription.session.event.name.{{config('app.locale')}} %>
                            <span ng-if="inscription.session.is_numbered"> -
                                <% inscription.slot.name %>
                            </span>
                            <br/>
                        </span>
                    </td>
                    <td>
                        <% pack.price %>€
                    </td>
                    <td>
                        <button class="btn btn-sm btn-default pull-right" type="button" ng-click="removeItem($index);"><span class="sr-only">{{ trans('tincket/backend.ticket.delete_item') }}</span><i class="fa fa-trash" role="presentation" aria-hidden="true"></i></button>
                    </td>
                </tr>
                <tr>
                    <td colspan="99">
                        <button class="btn btn-sm btn-default pull-right alert-success" type="button"  data-toggle="modal" data-target="#packsModal"><span class="sr-only">{{ trans('tincket/backend.ticket.new_pack') }}</span><i class="fa fa-plus" role="presentation" aria-hidden="true"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>{{ trans('tincket/backend.ticket.total') }}</b>
                    </td>
                    <td>
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

<!-- LAYOUT MODAL FOR PACKS SELLING -->
<div id="packsModal" class="modal fade" tabindex="-1" role="dialog" ng-controller="PackConfiguratorController">
    <div class="modal-dialog modal-lg" role="document" style="width: 95%;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 ng-show="step == 0">{{ trans('tincket/backend.ticket.select_the_pack') }}</h4>
                <h4 ng-show="step == 1">{{ trans('tincket/backend.ticket.select_the_sessions') }}</h4>
                <h4 ng-show="step == 2">{{ trans('tincket/backend.ticket.select_slots_for') }} <strong><% current_session.event.name.{{ config('app.locale')}} %></strong></h4>
            </div>
            <div class="modal-body" ng-show="step == 0">
                <div class="row" style="padding-bottom: 2rem;">
                    <div class="col-xs-2">
                        <a class="btn btn-default" href="{{ Request::url().'?'.http_build_query(['show_expired' => true]) }}">{{ trans('tincket/backend.ticket.show_all_packs')}}</a>
                    </div>
                </div>
                <div class="row">
                    @foreach($packs as $pack)
                    @php
                        // remove description to prevent broke js json if containts ; or ""
                        $pack->description = null;
                    @endphp
                    
                    <div class="col-xs-4" ng-click="selectPack({{ $pack }}); step = step + 1;">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <?php echo $pack->name ?>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-body" ng-show="step == 1">
                <div class="alert alert-info" ng-show="min_session_selection > 0 && !all_sessions">
                    <span class="glyphicon glyphicon-info-sign"></span> {{ trans('tincket/backend.ticket.select_at_least') }} , <strong><% min_session_selection %></strong> {{ trans('tincket/backend.ticket.sessions_to_sell_this_pack') }}.
                </div>
                <div class="row">
                    <div ng-repeat="event in events">
                        <div class="col-xs-4" ng-repeat="session in event.sessions">
                            <div class="panel panel-default" ng-class="{ 'alert-success': session.is_selected }" ng-click="toggleSession(session);">
                                <div class="panel-body">
                                    <% event.name.{{ config('app.locale')}} %><span ng-if="session.name.{{ config('app.locale')}}"> - <% session.name.{{ config('app.locale')}} %></span> (<% session.starts_on %>)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-body" ng-if="step == 2">
                <div class="row row-packs">
                    <div class="col-xs-1">
                        <a class="left carousel-control" style="width: 100%;" role="button" data-slide="prev" ng-click="previousSession()" ng-if="current_session_index > 0;">
                            <span class="glyphicon glyphicon-chevron-left" aria-hidden="true" style="color: grey;"></span>
                        </a>
                    </div>
                    <div class="col-xs-7">
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

                        <div ng-if="!current_session.is_numbered" class="row modal-body">
                            <div class="col-xs-6 col-sm-offset-3">
                                <div class="alert alert-info">
                                    <span class="glyphicon glyphicon-info-sign"></span> {{ trans('tincket/backend.ticket.sessio_no_numerada') }}<br>
                                    <div ng-if="current_session.free_positions < 30">
                                        {{ trans('tincket/backend.ticket.there_is_only') }}<strong><% current_session.free_positions %></strong>{{ trans('tincket/backend.ticket.free_slots_in_session') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-1">
                        <a class="right carousel-control col-xs-1" style="width: 100%;" role="button" data-slide="next" ng-click="nextSession();" ng-if="packService.getSelectedSessions()[current_session_index + 1]">
                            <span class="glyphicon glyphicon-chevron-right" aria-hidden="true" style="color: grey;"></span>
                        </a>
                    </div>
                    <div class="col-xs-3">

                        <ul class="list-group small" ng-repeat="session in packService.getSelectedSessions()">
                            <li class="list-group-item clearfix" ng-class="{active: $index == current_session_index}"><% session.event.name.{{ config('app.locale')}} %><span class="pull-right"><% session.is_numbered ? session.selection.length : pack_multiplier %>/<% pack_multiplier %> slots</span></li>
                            <li class="list-group-item" ng-if="session.is_numbered && !session.selection.length"><span class="text-danger">{{ trans('tincket/backend.ticket.pendent') }}</span></li>
                            <li class="list-group-item" ng-if="!session.is_numbered"><span class="text-success">{{ trans('tincket/backend.ticket.sessio_no_numerada') }}</span></li>
                            <li class="list-group-item" ng-repeat="inscription in session.selection"><% inscription.slot.name %></li>
                        </ul>

                        <!--
                        <ul class="list-group">
                            <li ng-repeat="inscription in inscriptions" class="list-group-item">
                                <% inscription.session.event.name.{{ config('app.locale') }} %><span ng-show="inscription.slot.name"> - <% inscription.slot.name %><span>
                            </li>
                        </ul>
                        -->
                    </div>
                </div>
            </div>
            <div class="modal-footer form-inline">
                <button type="button" class="btn" ng-click="reset()">{{ trans('tincket/backend.ticket.reset') }}</button>
                <div class="form-group" ng-show="step == 1">
                    <label>{{ trans('tincket/backend.ticket.how_many_packs') }}?</label>
                    <select class="form-control" ng-model="pack_multiplier">
                        <option ng-repeat="number in options" ng-selected="pack_multiplier == 1"><% number %></option>
                    </select>
                </div>
                <!-- <button type="button" class="btn btn-primary" ng-click="step = step - 1;">Back</button> -->
                <button type="button" class="btn btn-primary" ng-disabled="!isNextStepReady()" ng-click="nextStep();">Next</button>
                <!-- <button type="button" class="btn btn-primary" data-dismiss="modal">Add to cart</button> -->
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<!-- END MLAYOUT MODAL -->

@push('after_scripts')
<script type="text/javascript">
// ugly way to pass data to angular service
</script>

<script type="text/javascript" src="{{ asset('js/crud/ticket-office/angular-create-packs.js')}}?v={{ time() }}"></script>
@endpush
