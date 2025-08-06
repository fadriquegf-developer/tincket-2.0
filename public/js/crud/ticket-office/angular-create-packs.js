window.ticketOfficeApp = window.ticketOfficeApp || angular.module('ticketOfficeApp', [], function ($interpolateProvider) {
    $interpolateProvider.startSymbol('<%');
    $interpolateProvider.endSymbol('%>');
});
window.ticketOfficeApp.controller('PackController', ['$scope', 'PackService', function ($scope, packService) {
        $scope.packService = packService;

        $scope.removeItem = function ($index) {
            //Si al eliminar, quedan menos packs que los minimos por compra, borramos todos
            if(packService.packs.length <= pack_multiplier){
                packService.packs = [];
            }else{
                packService.packs.splice($index, 1);
            }
        }

        $scope.getTotal = function (pack) {
            return packService.getTotal(pack);
        }

        $scope.prepareJson = function (pack) {
            var json = {
                pack_id: pack.id,
                selection: _.map(pack.inscriptions, function (inscription) {
                    return {
                        session_id: inscription.session.id,
                        is_numbered: inscription.session.is_numbered,
                        slot_id: inscription.slot ? inscription.slot.id : null
                    };
                })
            };

            return JSON.stringify(json);
        }
    }]);
window.ticketOfficeApp.controller('PackConfiguratorController', ['$scope', 'PackService', 'InscriptionService', 'GiftService', function ($scope, packService, inscriptionService, giftService) {

        // ATTRIBUTES
        $scope.step = 0;
        $scope.pack_multiplier = 1;
        $scope.selected_sessions_counter = packService.countSelectedSessions();
        $scope.current_session_index = 0;
        $scope.current_session = packService.getSelectedSessions()[$scope.current_session_index];
        $scope.inscriptions = packService.inscriptions;
        $scope.max = 10;
        $scope.packService = packService;
        $scope.inscriptionService = inscriptionService;
        $scope.giftService = giftService;
        $scope.min_session_selection = 1;
        $scope.max_session_selection = 0;
        $scope.all_sessions = false;
        $scope.events = packService.getEvents();

        // METHODS
        $scope.selectPack = function (pack) {
            packService.selectPack(pack);
            pack_multiplier = pack.min_per_cart;
            max = pack.max_per_cart;
        };

        $scope.range = function(min, max, step){
            step = step || 1;
            var input = [];
            for (var i = min; i <= max; i += step) input.push(i);
            return input;
        };

        $scope.toggleSession = packService.toggleSession;
        
        $scope.nextSession = function ()
        {
            $scope.current_session_index++;
            onSessionChanged();
        };
        $scope.previousSession = function ()
        {
            $scope.current_session_index--;
            onSessionChanged();
        };
        $scope.nextStep = function ()
        {
            if ($scope.step == 1) {
                packService.resetInscriptions();
            }
            if ($scope.step == 2) {
                $("#packsModal").modal('hide');
                $scope.reset();
                finishPack();
            }
            $scope.step++;
        };
        $scope.addInscription = function (session, slot) {
            if (session.selection.length < $scope.pack_multiplier) {
                packService.addInscription(session, slot);
                // inscription was added
                return true;
            }

            return false;
        }

        $scope.removeInscription = function (session, slot) {
            session.already_selected = false;
            packService.removeInscription(session, slot);
        }

        $scope.reset = function () {
            $scope.step = 0;
            $scope.min_session_selection = 1;
            $scope.max_session_selection = 0;
            $scope.all_sessions = false;
        };

        $scope.isNextStepReady = function ()
        {
            // selected bettween min max or all_sessions
            if (
                $scope.step == 1 &&
                ((packService.countSelectedSessions() >=
                    $scope.min_session_selection &&
                    packService.countSelectedSessions() <=
                        $scope.max_session_selection) ||
                    $scope.all_sessions === true)
            )
                return true;

            // all sessions has the number of pack_multiplier slots selected
            if ($scope.step == 2) {
                var min_selection = _.chain(packService.getSelectedSessions())
                        .filter(function (session) {
                            return session.is_numbered;
                        })
                        .min(function (session) {
                            return session.selection.length;
                        })
                        .value();
                if(min_selection.selection){
                    min_selection = min_selection.selection.length
                }else{
                    min_selection = $scope.pack_multiplier;
                }

                return min_selection == $scope.pack_multiplier;
            }

            return false;
        }

        // PRIVATE METHODS

        var range = function(min, max, step){
            step = step || 1;
            var input = [];
            for (var i = min; i <= max; i += step) input.push(i);
            return input;
        };

        // when a pack is selected we load the events of this pack in this
        // private method that will be called by the PackService
        var loadSessionsFromPack = function () {
            $scope.events = packService.getEvents();
            $scope.min_session_selection = packService.getMinimumNumberOfSessions();
            $scope.max_session_selection = packService.getMaximumNumberOfSessions();
            $scope.all_sessions = packService.isAllSessions();
            $scope.options = range(packService.getMinPerCart(), packService.getMaxPerCart());
        };
        var onSessionChanged = function () {
            var session = packService.getSelectedSessions()[$scope.current_session_index];
            // add variable to match not pack service
            session.zoom = session.space.zoom;
            $scope.current_session = session;
            $scope.layout = $scope.current_session.space.svg_host_path;
        };
        var finishPack = function ()
        {
            for (var i = 0; i < $scope.pack_multiplier; i++)
            {
                var selectedPack = packService.getSelectedPack();
                var selectedPackRule = packService.getRule(selectedPack.sessions.length);
                var total = 0;

                var pack = {
                    id: selectedPack.id,
                    name: selectedPack.name,
                    inscriptions: []
                };
                for (var j = 0; j < selectedPack.sessions.length; j++)
                {
                    var session = selectedPack.sessions[j];
                    //  calcule price
                    let price = parseFloat(session.price);
                    if(selectedPackRule.price_pack){
                        total += price;
                    }else{
                        // % pack
                        const ratio = (100 - parseFloat(selectedPackRule.percent_pack)) / 100;
                        console.log("ratio", ratio);
                        price = roundToDecimals(price * ratio, 4); // 4 decimals
                        console.log(price * ratio, total);

                        if(selectedPack.round_to_nearest && selectedPackRule.percent_pack)
                        {
                            console.log('arrodoniment to 0.5');
                            $cost = price;
                            $factor = 0.5;
                            $cost = ($cost / $factor);
                            price = roundToDecimals($cost) * $factor;
                        }

                        total += price;
                    }
                    
                    pack.inscriptions.push({
                        session: session,
                        slot: session.is_numbered ? session.selection[i].slot : null
                    });
                }

                // calcule price
                if(selectedPackRule.price_pack){
                    total = selectedPackRule.price_pack;
                }else{
                    total = roundToDecimals(total, 2);
                }
                pack.price = total;

                packService.packs.push(pack);
            }
        }

        var roundToDecimals = function (number, decimals = 0) {
            const factor = Math.pow(10, decimals);
            const rounded = Math.round(number * factor) / factor;
            return rounded;
        }

        // LISTENERS
        $scope.$watch('step', function (new_step) {
            if (new_step == 2) {
                $scope.current_session_index = 0;
                onSessionChanged();
            }
        });
        packService.registerObserverCallback(loadSessionsFromPack);

        $('#packsModal').on('shown.bs.modal', function (e) {
            $scope.reset();
            // update ui
            $scope.$apply();
        });
    }]);
