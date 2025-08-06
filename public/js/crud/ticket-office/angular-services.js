/**
 * Service to handle inscriptions to sessions
 */
window.ticketOfficeApp.service('InscriptionService', function () {

    var observerCallbacks = [];

    //register an observer
    this.registerObserverCallback = function (callback) {
        observerCallbacks.push(callback);
    };

    //call this when you know 'foo' has been changed
    var notifyObservers = function () {
        angular.forEach(observerCallbacks, function (callback) {
            callback();
        });
    };

    this.inscriptions = [];

    this.addInscription = function (session, slot, rate) {
        var inscription = {
            session: session,
            slot: slot
        };
        // if there is not slot (is non numbered inscription), the rates are
        // taken from session
        if (slot === null) {
            
            // if(rate.available == 0){
            //     new PNotify({
            //         title: "Alerta",
            //         text: "Les entrades previstes per aquesta tarifa ja estan esgotades. Està realitzant una sobre venda sobre aquesta tarifa.",
            //         type: "warning"
            //     });
            // }

            inscription.slot = {
                rates: session.rates
            };

            // for non numbered sessions, rate may already be set
            inscription.selected_rate = rate;
            inscription.price = rate.price;
        }else{
            // numbered sessions
            if(slot.rates.length > 0){
                var auxRate = slot.rates[0];
                inscription.selected_rate = auxRate;
                inscription.price = auxRate.pivot.price
            }
        }

        this.inscriptions.push(inscription);
        notifyObservers();
    };

    this.removeInscription = function (index) {
        this.inscriptions.splice(index, 1);
        notifyObservers();
    };

    this.getTotal = function (index) {
        let total = 0;
        for(let i = 0; i <  this.inscriptions.length; i++){
            total += parseFloat(this.inscriptions[i].price);
        }
        return total;
    };

    this.findInscriptionIndex = function (session, slot, rate)
    {
        return _.findIndex(this.inscriptions, function (i) {
            return i.session.id === session.id && (slot === null || i.slot.id == slot.id) && (typeof (rate) === 'undefined' || i.selected_rate.id === rate.id);
        });
    };

    this.findInscription = function (session, slot)
    {
        var index = this.findInscriptionIndex(session, slot);
        if (index > -1)
        {
            return this.inscriptions[index];
        }

        return null;
    };

    this.hasInscription = function (session, slot)
    {
        return this.findInscriptionIndex(session, slot) > -1;
    };

    /**
     * Counts how many inscriptions for a given session and rate are in cart.
     * Used for non numbered sessions
     *
     * @param {type} session
     * @param {type} rate
     * @returns {undefined}
     */
    this.countInscriptions = function (session, rate) {
        return _.chain(this.inscriptions).filter(function (i) {
            return i.session.id == session.id && i.selected_rate.id == rate.id;
        }).size().value();
    };


});

/**
 * Service to handle the selection of the current session
 */
window.ticketOfficeApp.service('SessionService', function () {
    var observerCallbacks = [];

    //register an observer
    this.registerObserverCallback = function (callback) {
        observerCallbacks.push(callback);
    };

    //call this when you know 'foo' has been changed
    var notifyObservers = function () {
        angular.forEach(observerCallbacks, function (callback) {
            callback();
        });
    };

    var current_session = null;

    this.setCurrentSession = function (session) {
        current_session = session;
        current_session.sold = 0;
        notifyObservers();
    }
    this.getCurrentSession = function ()
    {
        return current_session;
    }
});

/**
 * Service to manage slots map
 */
window.ticketOfficeApp.service('SlotMapService', ['$http', 'SessionService', function ($http, sessionService) {
        this.zoomist_pack = null;
        this.zoomist_inscription = null;
        var slotMap = null;

        this.loadSessionMap = function (session_id)
        {
            slotMap = null;

            var url = '/api/session/' + session_id + '/configuration?' + Math.random(); // just to prevent cache
            
            // show loader
            var loader = $('#loading');
            loader.show();

            // fetch space configuration
            $http.get(url)
                    .success(function (data) {
                        slotMap = data;
                        sessionService.getCurrentSession().free_positions = data.free_positions;
                        sessionService.getCurrentSession().zoom = data.zoom;
                    })
                    .finally(function() {
                        loader.hide();
                    });

            return this;
        };

        this.getSlot = function (id) {
            if (!slotMap)
                return false;

            return _(slotMap.zones).chain().pluck('slots').flatten().findWhere({id: id}).value();
        };

        this.isReady = function ()
        {
            return slotMap != null;
        };
    }]);

window.ticketOfficeApp.directive('spaceLayout', ['SlotMapService', function (slotMapService) {
        return {
            restrict: 'E',
            scope: {
                layout: '=layoutUrl',
                current_session: '=layoutSession',
                onAddInscription: '=',
                onRemoveInscription: '=',
                inscriptionService: '=',
                packService: '=',
                giftService: '=',
                typeModel: '@',
            },
            link: function ($scope, element, attrs, controller, transcludeFn) {
                /**
                 * Loads a new layout resource
                 */
                var updateLayout = function ()
                {
                    $.get($scope.layout, function (data) {
                        element.html($("<object class='selection-wrap' id='svg-object'>").html(data));
                        $(element).find('svg').height('90%').width('90%');
                        bindEventsToLayout();
                    }, 'text');
                };

                var getBusyColor = function (slot) {
                    switch (slot.lock_reason) {
                        case 1:
                            return 'dimgrey';
                        case 3:
                            return 'purple';
                        case 4:
                            return '#b9b9b9';
                        case 5:
                            return 'transparent';
                        case 6:
                        case 7:
                            return '#0368ae';
                        case 8:
                            return 'mediumslateblue';
                        case 2:
                        default:
                            return '#e53935';
                    }
                }

                let hasMoved = 0;
                let touchStartX = 0;
                let touchStartY = 0;

                var bindEventsToLayout = function ()
                {
                    let slot = $(element).find('.slot');

                    // remove pervius events
                    slot.off();

                    slot.on("pointerdown", function (event) {
                        hasMoved = false
                        touchStartX = event.clientX;
                        touchStartY = event.clientY;
                    });

                    slot.on("pointermove", function (event) {
                            const touchX = event.clientX;
                            const touchY = event.clientY;
                            const deltaX = Math.abs(touchX - touchStartX);
                            const deltaY = Math.abs(touchY - touchStartY);
                      
                            if (!hasMoved && (deltaX > 10 || deltaY > 10)) {
                              // Consider it a zoom gesture if the user moved their finger more than 10 pixels
                              hasMoved = true;
                              // Perform your zoom action here using your library
                            }
                    });

                    slot.on("pointerup", function () {
                        if (!hasMoved) {
                            // is a click and not a zoom gestue
                            $(this).trigger('slotClick');
                        }
                    });

                    slot.on("slotClick", function () {
                        var was_checked = $(this).prop('checked');
                        var slot_id = $(this).data('slot-id');

                        if (!$(this).prop('checked'))
                        {
                            var added = false;
                            $scope.$apply(function () {
                                added = $scope.onAddInscription($scope.current_session, slotMapService.getSlot(slot_id));
                            });
                            if (added) {
                                $(this).data('original-color', $(this).css('fill'));
                                $(this).css('fill', 'orange');
                                $(this).prop('checked', true);
                            }
                        } else if (was_checked) {
                            $scope.$apply(function () {
                                $scope.onRemoveInscription($scope.current_session, slotMapService.getSlot(slot_id));
                            });
                            $(this).prop('checked', false);
                            $(this).css('fill', $(this).data('original-color'));
                        }
                    });


                    const container = $(element).find('#svg-object');
                    let zoomist = slotMapService["zoomist_" + $scope.typeModel] || null;
                    if (zoomist) {
                        try {
                            zoomist.reset();
                        } catch (error){}
                        zoomist.destroy();
                    }

                    if($scope.current_session.zoom){
                        if($('.modal.in, .modal.show').length){
                            slotMapService["zoomist_" + $scope.typeModel] = new Zoomist('.zoomist-container-' + $scope.typeModel, {
                                // Optional parameters
                                maxScale: 7,
                                bounds: true,
                                // if you need slider
                                slider: true,
                                // if you need zoomer
                                zoomer: true,
                                on: {
                                    ready(zoomist, scale) {
                                        // Fix to prevent zoom butons to zubmit form
                                        $('.zoomist-zoomer-button').attr("type", "button");
                                    }
                                }
                            })
                        }
                    }

                    // disable selection multiple on packs or if has zoom
                    if($scope.typeModel === 'pack' || $scope.current_session.zoom){
                        container.addClass('pack');
                        // btn selection multiple
                        $('#to-add').hide();
                        $('#to-remove').hide();
                    }else{
                        container.removeClass('pack');
                        // btn selection multiple
                        $('#to-add').show();
                        $('#to-remove').show();
                    }
                }

                var timeout = null;

                /**
                 * Loads a nes slot state map on the loaded layout
                 */
                var updateSessionMap = function ()
                {
                    if (!$scope.current_session.is_numbered)
                        return false;

                    $(element).find('.slot').prop('checked', false);
                    $(element).find('.slot').css('fill', '#a3d165');
                    if (slotMapService.isReady())
                    {
                        bindEventsToLayout();
                        let count = 0;

                        $(element).find('.slot').each(function () {
                            var $slot = $(this);
                            var slot = slotMapService.getSlot($slot.data('slot-id')); 
                            
                            // prepare popover
                            let strStatus = "";
                            if(slot.lock_reason != null) {
                                strStatus = slotStatus[slot.lock_reason]
                            }
                            $slot.popover('destroy');
                            $slot.addClass('free');
                            $slot.attr('data-toggle', 'popover');
                            $slot.attr('data-trigger', 'hover');
                            $slot.attr('data-content', [strStatus, slot.comment].join('<br>'));
                            $slot.attr('data-container', 'body');
                            $slot.attr('data-placement', 'auto');

                            $slot.removeClass('border-slot');

                            if ($scope.inscriptionService.hasInscription($scope.current_session, slot)) {
                                if($scope.typeModel === 'inscription') {
                                        $slot.data('original-color', getBusyColor(slot));
                                        $slot.prop('checked', true);
                                        $slot.css('fill', 'orange');
                                } else {
                                        $slot.css('fill', '#e53935'); // free
                                        $slot.off();
                                        $slot.removeClass('free');
                                }
                                   
                            } else if ($scope.packService.hasInscription($scope.current_session, slot)) {
                                if ($scope.typeModel === 'pack') {
                                    $slot.data('original-color', getBusyColor(slot));
                                    $slot.prop('checked', true);
                                    $slot.css('fill', 'orange');
                                } else {
                                    $slot.css('fill', '#e53935'); // free
                                    $slot.off();
                                    $slot.removeClass('free');
                                }
                                   
                            } else if (slot.is_locked && slot.lock_reason == 3) {
                                // slot is locked because it is booked
                                $slot.css('fill', getBusyColor(slot));
                            }else if (slot.is_locked && slot.lock_reason == 8) {
                                // slot is locked because it is booked for packs
                                $slot.css('fill', getBusyColor(slot));
                                if($scope.typeModel === 'inscription'){
                                    $slot.off();
                                    $slot.removeClass('free');
                                }
                            }else if (slot.is_locked && slot.lock_reason == 1) {
                                // slot is locked because it is locked
                                $slot.css('fill', getBusyColor(slot));
                                $slot.off();
                                $slot.removeClass('free');
                            }else if (slot.is_locked && slot.lock_reason == 4) {
                                // slot is locked because it is covid
                                $slot.css('fill', getBusyColor(slot));
                                $slot.off();
                                $slot.removeClass('free');
                            }else if (slot.is_locked && slot.lock_reason == 5) {
                                // slot is locked because it is hidden
                                $slot.css('fill', getBusyColor(slot));
                                $slot.off();
                                $slot.removeClass('free');
                                $slot.addClass('border-slot');
                            }else if (slot.lock_reason == 6) {
                                // slot is locked beacuse reduction mobility clicable
                                $slot.css('fill', getBusyColor(slot));
                            }else if (slot.is_locked && slot.lock_reason == 7) {
                                // slot is locked beacuse reduction mobility
                                $slot.css('fill', getBusyColor(slot));
                            }else if (slot.is_locked){
                                $slot.css('fill', getBusyColor(slot));
                                $slot.off();
                                $slot.removeClass('free');
                                count++;
                            }else if($scope.packService.hasInscriptionAllPacks($scope.current_session, slot)){
                                $slot.css('fill', getBusyColor(slot));
                                $slot.off();
                                $slot.removeClass('free');
                            }

                            // create popover
                            $slot.popover({title: slot.name, html: true});
                        });
                        
                        $scope.current_session.sold = count;
                    } else {
                        if (timeout) {
                            clearTimeout(timeout);
                        }
                        timeout = setTimeout(updateSessionMap, 500);
                    }

                    _.defer(function(){$scope.$apply();});
                };

                $scope.$watch(attrs.layoutUrl, updateLayout);
                $scope.$watch(attrs.layoutSession, function () {
                    slotMapService.loadSessionMap($scope.current_session.id);
                    updateLayout();
                    updateSessionMap();
                });

                // needs to be improved. The point is an slot is unchecked when deleted from list
                //inscriptionService.registerObserverCallback(updateSessionMap);

                $scope.$on("$destroy", function () {
                    if (timeout) {
                        clearTimeout(timeout);
                    }
                });
            }
        };
    }]);

ticketOfficeApp.service('PackService', ['$http', function ($http) {
        var events = [];
        var selected_pack = null;
        var selected_sessions = [];
        var selected_pack_rules = [];
        var min_number_of_sessions = 1;
        var max_number_of_sessions = 0;
        var all_sessions = false;
        var min_per_cart = 1;
        var max_per_cart = 10;
        this.selected_sessions_counter = 0;

        // used internally to remeber selected slots and paint them orange
        var inscriptions = [];

        // SELECTION DATA
        this.packs = [];

        var observerCallbacks = [];

        //register an observer
        this.registerObserverCallback = function (callback) {
            observerCallbacks.push(callback);
        };

        //call this when you know 'foo' has been changed
        var notifyObservers = function () {
            angular.forEach(observerCallbacks, function (callback) {
                callback();
            });
        };

        this.getEvents = function () {
            return events;
        };

        var loadEvents = function () {
            var url = '/api/pack/' + selected_pack.id;
            events.splice(0, events.length); //clear sessions array withou using new space
            selected_sessions.splice(0, selected_sessions.length); //clear sessions array withou using new space
            selected_pack_rules.splice(0, selected_pack_rules.length);
            notifyObservers();

            // show loader
            var loader = $('#loading');
            loader.show();

            // fetching selected pack information
            $http.get(url)
                    .success(function (data) {
                        events = _.filter(data.events, function (event) {
                            return event.sessions.length > 0;
                        });
                        
                        selected_pack_rules = data.rules;

                        min_number_of_sessions = _.min(data.rules, function (rule) {
                            return rule.number_sessions;
                        }).number_sessions || 1;

                        max_number_of_sessions = _.max(data.rules, function (rule) {
                            return rule.number_sessions;
                        }).number_sessions || 0;

                        if(data.rules.length === 1 && data.rules[0].all_sessions){
                            all_sessions = true;
                        }else{
                            all_sessions = false;
                        }

                        min_per_cart = data.min_per_cart;
                        max_per_cart = data.max_per_cart;

                        _.each(events, function (event) {
                            _.each(event.sessions, function (session) {
                                session.event = event;
                                session.selection = [];

                                // select all sessions
                                if(all_sessions) {
                                    selected_pack.sessions = selected_pack.sessions || [];
                                    selected_pack.sessions.push(session);
                                    selected_sessions.push(session);
                                    session.is_selected = true;
                                    this.selected_sessions_counter++;
                                }
                            });
                        });

                        notifyObservers();
                    }).finally(function() {
                        loader.hide();
                    });
        };

        this.selectPack = function (pack) {
            selected_pack = pack;
            loadEvents();
        };

        this.toggleSession = function (session)
        {
            if(all_sessions){
                return false;
            }

            var index = _.findIndex(selected_sessions, function (_session) {
                return _session.id === session.id;
            });

            if (index === -1) {
                selected_pack.sessions = selected_pack.sessions || [];
                selected_pack.sessions.push(session);
                selected_sessions.push(session);
                session.is_selected = true;
                this.selected_sessions_counter++;
            } else {
                selected_pack.sessions.splice(index, 1);
                selected_sessions.splice(index, 1);
                session.is_selected = false;
                this.selected_sessions_counter--;
            }
        };

        this.getMinimumNumberOfSessions = function () {
            return min_number_of_sessions;
        };

        this.getMaximumNumberOfSessions = function () {
            return max_number_of_sessions;
        };

        this.isAllSessions = function () {
            return all_sessions;
        };

        this.getMinPerCart = function () {
            return min_per_cart;
        };

        this.getMaxPerCart = function () {
            return max_per_cart;
        };


        this.countSelectedSessions = function ()
        {
            return selected_sessions.length;
        };

        this.getSelectedSessions = function () {
            return selected_sessions;
        };

        this.addInscription = function (session, slot, rate) {
            var inscription = {
                slot: slot
            };

            // if there is not slot (is non numbered inscription), the rates are
            // taken from session
            if (slot === null) {
                inscription.slot = {
                    rates: session.rates
                };
            }

            // for non numbered sessions, rate may already be set
            inscription.selected_rate = rate;

            session.selection.push(inscription);

            notifyObservers();

            // used internally to "remember" what slots are selected and painted orange
            inscriptions.push({
                session: session,
                slot: slot
            });

            return inscription;
        };

        this.removeInscription = function (session, slot) {
            if (this.hasInscription(session, slot)) {
                inscriptions.splice(this.findInscriptionIndex(session, slot), 1);

                session.selection = _.reject(session.selection, function (inscription) {
                    return inscription.slot.id === slot.id;
                });

                notifyObservers();
            }
        };

        this.resetInscriptions = function ()
        {
            inscriptions.splice(0, inscriptions.length);
        }

        this.findInscriptionIndex = function (session, slot, rate)
        {
            return _.findIndex(inscriptions, function (i) {
                return i.session.id === session.id && (slot === null || i.slot.id == slot.id);
            });
        };

        this.findInscription = function (session, slot)
        {
            var index = this.findInscriptionIndex(session, slot);
            if (index > -1)
            {
                return inscriptions[index];
            }

            return null;
        };

        this.hasInscription = function (session, slot)
        {
            return this.findInscriptionIndex(session, slot) > -1;
        };

        this.getSelectedPack = function () {
            return selected_pack;
        };

        this.getRule = function (n) {
            return selected_pack_rules.find(rule => rule.number_sessions === n || rule.all_sessions === 1);
        };

        this.getTotal = function () {
            let total = 0;
            for(let i = 0; i <  this.packs.length; i++){
                total += parseFloat(this.packs[i].price);
            }
            return total;
        };

        this.hasInscriptionAllPacks = function (session, slot)
        {
            return _.findIndex(this.packs, function (p) {
                return _.findIndex(p.inscriptions, function (i) {
                        return i.session.id === session.id && (slot === null || i.slot.id == slot.id);
                }) > -1;
            }) > -1;
        };
    }]);

    ticketOfficeApp.service('GiftService', ['$http', function ($http) {
        var event = null;
        var current_session = null;
        var current_code = null;

        // list selected inscriptions
        this.inscriptions = [];

        var observerCallbacks = [];

        //register an observer
        this.registerObserverCallback = function (callback) {
            observerCallbacks.push(callback);
        };

        //call this when you know 'foo' has been changed
        var notifyObservers = function () {
            angular.forEach(observerCallbacks, function (callback) {
                callback();
            });
        };

        this.setEvent = function (ev) {
            event = ev;
        };

        this.getEvent = function () {
            return event;
        };

        this.setCurrentSession = function (session) {
            current_session = session;
            this.toggleSession(session);
        };

        this.setCurrentCode = function (code) {
            current_code = code;
        };

        this.getCurrentSession = function () {
            return current_session;
        };

        this.toggleSession = function (session)
        {
            current_session = session;

            event.next_sessions.map(s => {
                s.is_selected = session && s.id === session.id;

                return s;
            });
        };

        this.prepareInscription = function (session, slot) {
            var inscription = {
                session: session,
                slot: slot,
                code: current_code
            };
            // if there is not slot (is non numbered inscription), the rates are
            // taken from session
            if (slot === null) {
                inscription.slot = {
                    rates: session.rates
                };
            }else{
                // numbered sessions
                if(slot.rates.length > 0){
                    var auxRate = slot.rates[0];
                    inscription.selected_rate = auxRate;
                }
            }

            return inscription;
        };

        this.addInscription = function (inscription) {
            this.inscriptions.push(inscription);
            notifyObservers();
        };

        this.removeInscription = function (index) {
            this.inscriptions.splice(index, 1);
            notifyObservers();
        };

        this.resetInscriptions = function ()
        {
            inscriptions.splice(0, inscriptions.length);
        }

        this.findInscriptionIndex = function (session, slot, rate)
        {
            return _.findIndex(this.inscriptions, function (i) {
                return i.session.id === session.id && (slot === null || i.slot.id == slot.id) && (typeof (rate) === 'undefined' || i.selected_rate.id === rate.id);
            });
        };

        this.findInscription = function (session, slot)
        {
            var index = this.findInscriptionIndex(session, slot);
            if (index > -1)
            {
                return this.inscriptions[index];
            }

            return null;
        };

        this.hasInscription = function (session, slot)
        {
            return this.findInscriptionIndex(session, slot) > -1;
        };

        this.hasCode = function (code)
        {
            return _.findIndex(this.inscriptions, function (i) {
                return i.code === code;
            }) > -1;
        };
    }]);
