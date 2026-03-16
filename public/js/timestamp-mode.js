// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

(function (Icinga) {

    "use strict";

    class TimestampToggle extends Icinga.EventListener {
        constructor(icinga)
        {
            super(icinga);

            this.on('change', '.timestamp-toggle', this.onTimestampModeToggle, this);
            this.on('click', '.interactive-time', this.onTimestampClick, this);
            this.dateFormatter = new Intl.DateTimeFormat(
                'en-US',
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

            container.querySelectorAll('.load-more .action-link, .refresh-container-control')
                .forEach((el) => {
                    let loadMoreUrl = el.getAttribute('href');
                    loadMoreUrl = window.icinga.utils.addUrlParams(loadMoreUrl, {timestamps: preference});
                    el.setAttribute('href', loadMoreUrl);
                });

            icinga.history.replaceCurrentState();

            if (event.target.checked) {
                container.querySelectorAll('.content .interactive-time').forEach(el => {
                    el.removeAttribute('data-absolute-time');
                    el.setAttribute('data-relative-time', 'ago');

                    const diff = Math.floor((
                        new Date(event.data.self.dateFormatter.format(new Date()))
                        - new Date(el.getAttribute('datetime'))
                    ) / 1000);

                    if (diff < 3600) {
                        const parts = el.getAttribute('data-ago-label').split(/\d+/);
                        el.textContent = parts[0]
                            + Math.floor(diff / 60) + parts[1]
                            + Math.floor(diff % 60) + parts[2];
                    }
                });
            } else {
                container.querySelectorAll('.content .interactive-time').forEach(el => {
                    el.removeAttribute('data-relative-time');
                    el.setAttribute('data-absolute-time', '');
                    el.textContent = event.data.self.timeFormatter
                        .format(new Date(el.getAttribute('datetime')));
                });
            }
        }
    }

    Icinga.Behaviors.TimestampToggle = TimestampToggle;
}(Icinga));