window.ticketOfficeApp = window.ticketOfficeApp || angular.module('ticketOfficeApp', ['ui.sortable'], function ($interpolateProvider) {
    $interpolateProvider.startSymbol('<%');
    $interpolateProvider.endSymbol('%>');
});

window.ticketOfficeApp.controller('LayoutController', ['$scope', 'InscriptionService', 'PackService', 'SessionService', 'SlotMapService', function ($scope, inscriptionService, packService, sessionService, slotService) {
        $scope.inscriptionService = inscriptionService;
        $scope.packService = packService;

        $scope.sessions = window.sessions_list; // ugly. what is the proper way?

        $scope.current_session = _.find($scope.sessions, function (s) {
            return !s.is_past;
        }) || _.last($scope.sessions);
        sessionService.setCurrentSession($scope.current_session);

        $scope.layout = $scope.current_session.space.layout;

        $scope.inscriptions = inscriptionService.inscriptions;

        $scope.updateLayout = function ()
        {
            sessionService.setCurrentSession($scope.current_session);
            $scope.layout = $scope.current_session.space.layout;

            if (!$scope.current_session.is_numbered)
            {
                angular.forEach($scope.current_session.rates, function (rate) {
                    rate.quantity = inscriptionService.countInscriptions($scope.current_session, rate);
                });
            }
        }

        $scope.addNonNumberedInscription = function (rate)
        {
            inscriptionService.addInscription($scope.current_session, null, rate);
        }

        $scope.removeNonNumberedInscription = function (rate)
        {
            var index = inscriptionService.findInscriptionIndex($scope.current_session, null, rate);
            if (index > -1)
                inscriptionService.removeInscription(index);
        }

        $scope.addInscription = function (session, slot)
        {
            inscriptionService.addInscription(session, slot);
            return true; // inscription was added
        };

        $scope.removeInscription = function (session, slot)
        {
            var index = inscriptionService.findInscriptionIndex(session, slot);
            if (index > -1)
                inscriptionService.removeInscription(index);
        };


        const clearSelection = function (selectedElements) {
            for (const el of selectedElements) {
                el.classList.remove('selected');
            }
    
            // Clear previous selection
            selection.clearSelection();
        };
    
        const selection = Selection.create({
            // Class for the selection-area
            class: 'selection-area',
    
            // All elements in this container can be selected
            selectables: ['.slot.free'],
    
            // The container is also the boundary in this case
            boundaries: ['.selection-wrap:not(.pack)']
        })
        .on('start', ({inst, selected, oe}) => {
            // Fix z.index modal
            inst.T.style.zIndex = 1150;

            // Remove class if the user isn't pressing the control key or ⌘ key
            if (!oe.ctrlKey && !oe.metaKey) {
        
                // Unselect all elements
                for (const el of selected) {
                    el.classList.remove('selected');
                    inst.removeFromSelection(el);
                }
        
                // Clear previous selection
                inst.clearSelection();
            }
        
        }).on('move', ({changed: {removed, added}}) => {
        
            // Add a custom class to the elements that where selected.
            for (const el of added) {
                el.classList.add('selected');
            }
        
            // Remove the class from elements that where removed
            // since the last selection
            for (const el of removed) {
                el.classList.remove('selected');
            }
        
        }).on('stop', ({inst}) => {
            inst.keepSelection();
        });

        $('#layoutModal').on('shown.bs.modal', function (e) {
            // simulate change repint layout
            $('.form-control.session').change();
        });
    }]);

window.ticketOfficeApp.controller('InscriptionController', ['InscriptionService', 'SessionService', '$scope', function (inscriptionService, sessionService, $scope) {
        var updateInscriptions = function () {
            $scope.inscriptions = inscriptionService.inscriptions;
        };
        inscriptionService.registerObserverCallback(updateInscriptions);

        $scope.inscriptions = $scope.inscriptions || updateInscriptions();

        $scope.updateRate = function (item, rate) {
            // update price
            if(item.selected_rate.pivot){
                // numered
                item.price = item.selected_rate.pivot.price;
            }else{
                // non numered
                item.price = item.selected_rate.price;
            }
        }

        $scope.getTotal = function () {
            return inscriptionService.getTotal();
        }

        $scope.removeItem = function (index) {
            // remove inscription
            inscriptionService.removeInscription(index);
        }

    }]);

    window.ticketOfficeApp.controller("GiftController", [
        "GiftService",
        "$scope",
        "$http",
        function (giftService, $scope, $http) {
            $scope.code = "";
    
            var updateInscriptions = function () {
                $scope.inscriptions = giftService.inscriptions;
            };
            giftService.registerObserverCallback(updateInscriptions);
    
            $scope.inscriptions = $scope.inscriptions || updateInscriptions();
    
            $scope.removeItem = function (index) {
                // remove inscription
                giftService.removeInscription(index);
            };
    
            $scope.validate = function () {
                if(giftService.hasCode($scope.code)){
                    new PNotify({
                        title: "Alerta",
                        text: "Ja tens aquest codi a la cistella",
                        type: "warning"
                    });
                    return false
                }

                if($scope.code){
                    // show loader
                    var loader = $("#loading");
                    loader.show();
        
                    var url = "/api/gift-card/validate?code=" + $scope.code;
                    $http
                        .get(url)
                        .success(function (data) {
                            if(data.success){
                                giftService.setEvent(data.event);
                                giftService.setCurrentCode($scope.code);
                                $scope.code = "";
                                $('#layoutGiftModal').modal('show'); 
                            }else{
                                new PNotify({
                                    title: "Alerta",
                                    text: "No s'ha trobat el codi o ja s'ha reclamat",
                                    type: "warning"
                                });
                            }
                        })
                        .finally(function () {
                            loader.hide();
                        });
                }
            };
        },
    ]);

    window.ticketOfficeApp.controller("GiftConfiguratorController", [
        "$scope",
        "GiftService",
        'InscriptionService',
        'PackService', 
        function ($scope, giftService, inscriptionService, packService) {
            // ATTRIBUTES
            var inscription = null;

            $scope.step = 0;
            $scope.current_session_index = 0;
            // $scope.current_session = giftService.getSelectedSessions()[$scope.current_session_index];
            $scope.packService = packService;
            $scope.inscriptionService = inscriptionService;
            $scope.giftService = giftService;
            // $scope.min_session_selection = 1;
            // $scope.max_session_selection = 0;
            // $scope.all_sessions = false;
            $scope.event = giftService.getEvent();
            modelName = "#layoutGiftModal";
    
            $scope.toggleSession = giftService.toggleSession;
    
            $scope.nextStep = function () {
                if($scope.step == 0){
                    onSessionChanged()
                }else if ($scope.step == 1) {
                    finish();
                }
                $scope.step++;
            };
    
            $scope.isNextStepReady = function () {
                if (
                    ($scope.step == 0 && giftService.getCurrentSession() !== null) ||
                    ($scope.step == 1 && inscription !== null)
                ) {
                    return true;
                }
    
                return false;
            };
    
            var onSessionChanged = function () {
                var session = giftService.getCurrentSession();
                // add variable to match not pack service
                session.zoom = session.space.zoom;
                $scope.current_session = session;
                $scope.layout = $scope.current_session.space.svg_host_path;

                // no numered prepare inscription
                if(!session.is_numbered){
                    inscription = giftService.prepareInscription(session, null);
                }
            };

            var finish = function ()
            {
                if(inscription){
                    giftService.addInscription(inscription);
                    giftService.setCurrentCode(null);
                    inscription = null;
                    $(modelName).modal("hide");
                    $scope.reset();
                }
            }

            $scope.addInscription = function (session, slot) {
                if(!inscription){
                    inscription = giftService.prepareInscription(session, slot);
                    return true;
                }
                return false;
            }
    
            $scope.removeInscription = function (session, slot) {
                giftService.removeInscription(session, slot);
                inscription = null;
            }
    
            $scope.reset = function () {
                $scope.step = 0;
                $scope.event = giftService.getEvent();
                inscription = null;
                giftService.setCurrentSession(null);
            };
    
            $(modelName).on("shown.bs.modal", function (e) {
                $scope.reset();
                // update ui
                $scope.$apply();
            });
        },
    ]);
