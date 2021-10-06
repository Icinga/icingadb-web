/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

;(function(Icinga, $) {

    'use strict';

    Icinga.Behaviors = Icinga.Behaviors || {};

    /**
     * Icinga DB States behavior.
     *
     * @param icinga {Icinga} The current Icinga Object
     */
    var States = function(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.icinga = icinga;

        this.icinga.timer.register(this.onRefresh, this, 500);
    };

    States.prototype = new Icinga.EventListener();

    States.prototype.onRefresh = function () {
        document.querySelectorAll('[data-state-interval]').forEach(e => {
            let interval = Number(e.dataset.stateInterval) * 1000;
            let lastUpdate = Number(e.dataset.lastUpdate);

            if (! isNaN(lastUpdate) && lastUpdate + interval > (new Date()).getTime()) {
                return;
            }

            e.dataset.lastUpdate = (new Date()).getTime().toString();

            let hostIds = [];
            let serviceIds = [];
            e.querySelectorAll('[data-host-id], [data-service-id]').forEach(e => {
                let hostId = e.dataset.hostId;
                hostIds.push(hostId);

                let serviceId = e.dataset.serviceId;
                if (!! serviceId) {
                    serviceIds.push(serviceId);
                }
            });

            let req = this.icinga.loader.loadUrl(
                'icingadb/state/update',
                $(e.closest('.container')),
                {
                    hosts: hostIds.join(','),
                    services: serviceIds.join(',')
                },
                'POST',
                undefined,
                true
            );
            req.addToHistory = false;
            req.scripted = true;
        });
    };

    Icinga.Behaviors.States = States;

})(Icinga, jQuery);
