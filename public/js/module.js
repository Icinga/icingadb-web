/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

(function (Icinga, $) {

    "use strict";

    class IcingaDB {
        /**
         * Constructor
         *
         * @param {Icinga.Module} module
         */
        constructor(module) {
            try {
                let requiredBehaviors = [
                    "icinga/icinga-php-library/compat/ActionListBehavior",
                    "icinga/icinga-php-library/compat/LoadMoreBehavior"
                ];

                requiredBehaviors.forEach((behaviorPath) => {
                    let Class = require(behaviorPath);
                    let behavior = new Class(module.icinga);
                    module.icinga.behaviors[Class.name.toLowerCase()] = behavior;
                    behavior.bind($(document));
                });
            } catch (e) {
                console.warn('Unable to provide behaviors. Libraries not available:', e);
                return;
            }

            this.icinga = module.icinga;
        }
    }

    Icinga.availableModules.icingadb = IcingaDB;

})(Icinga, jQuery);
