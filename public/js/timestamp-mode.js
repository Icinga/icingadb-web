/* Icinga DB Web | (c) 2026 Icinga GmbH | GPLv2 */

(function (Icinga) {

    "use strict";

    class TimestampToggle extends Icinga.EventListener {
        constructor(icinga)
        {
            super(icinga);

            this.on('change', '.timestamp-toggle', this.onTimestampModeToggle, this);
            this.on(
                'click',
                '.item-list .history .extended-info time, .item-list .notification .extended-info time',
                this.onTimestampClick,
                this
            );
            this.dateFormatter = new Intl.DateTimeFormat(
                icinga.config.locale,
                { dateStyle: 'medium', timeStyle: 'medium', timeZone: icinga.config.timezone }
            );
            this.timeFormatter = new Intl.DateTimeFormat(
                icinga.config.locale,
                { timeStyle: 'medium' }
            );
        }

        /**
         * Stop event propagation, so that the list-item underneath is not clicked,
         * click the columns timestamp-toggle instead
         *
         * @param event The click event of the timestamp
         */
        onTimestampClick(event)
        {
            event.stopPropagation();
            event.target.closest('.container').querySelector('.timestamp-toggle').click();
        }

        /**
         * Handle changed timestamp mode
         *
         * @param event The change event of the timestamp-toggle
         */
        onTimestampModeToggle(event)
        {
            const preference = (event.target.checked) ? 'relative' : 'absolute';
            const container = event.target.closest('.container');
            const url = window.icinga.utils.addUrlParams($(container).data('icingaUrl'), {timestamps: preference});
            $(container).data('icingaUrl', url);
            container.querySelectorAll('.load-more')
                .forEach((el) => {
                    let actionLink = el.querySelector('a');
                    let loadMoreUrl = actionLink.getAttribute('href');
                    loadMoreUrl = window.icinga.utils.addUrlParams(loadMoreUrl, {timestamps: preference});
                    actionLink.setAttribute('href', loadMoreUrl);
                });

            icinga.history.replaceCurrentState();
            // Update session and user Preferences, desired keys are e.g. icingadb/history
            let body = {};
            body[url.split('?')[0].replace(/^(\/icingaweb2\/)/,"")] = preference;
            fetch(location.origin + icinga.config.baseUrl + '/icingadb/history/timestamp-preference', {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(body)
            });

            $container.find(
                '.item-list .history .extended-info time, .item-list .notification .extended-info time'
            ).each(function () {
                const $el = $(this);
                if ($el.hasClass('time-ago')) {
                    $el.removeClass('time-ago')
                        .addClass('time-absolute');

                    $el.text(event.data.self.timeFormatter.format(new Date($el.attr('datetime'))));
                } else {
                    $el.removeClass('time-absolute')
                        .addClass('time-ago');

                    $el.text(event.data.self.relativeTime(new Date($el.attr('datetime'))));
                }
            });
        }

        /**
         * Get the relative time to be displayed for a date, returns absolute time if it exceeds one hour
         *
         * @param {Date} date The date to change to relative representation
         *
         * @returns {string} The representation of the date in relative time
         */
        relativeTime(date)
        {
            const timestamp = date.getTime();
            // Adjust to the user's timezone
            const now = new Date(this.dateFormatter.format(new Date()));
            const diff = (now.getTime() - timestamp) / 1000;
            if (diff >= 3600) {
                return this.timeFormatter.format(date);
            } else {
                return Math.floor(diff / 60) + "m " + Math.floor(diff % 60) + "s ago";
            }
        }
    }

    Icinga.Behaviors.TimestampToggle = TimestampToggle;
}(Icinga));