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
         * and toggle the timestamp mode of the container
         *
         * @param event The click event of the timestamp
         */
        onTimestampClick(event)
        {
            event.stopPropagation();

            const container = event.target.closest('.container');
            const toggle = container.querySelector('.timestamp-toggle');

            if (toggle) {
                toggle.click();
            } else {
                const isRelative = event.target.hasAttribute('data-relative-time');
                event.data.self.applyTimestampMode(container, ! isRelative);
            }
        }

        /**
         * Handle changed timestamp mode
         *
         * @param event The change event of the timestamp-toggle
         */
        onTimestampModeToggle(event)
        {
            const container = event.target.closest('.container');
            event.data.self.applyTimestampMode(container, event.target.checked);
        }

        /**
         * Apply the given timestamp mode to all interactive timestamps in the container
         *
         * @param {Element} container The container element
         * @param {boolean} relative  Whether to show relative timestamps
         */
        applyTimestampMode(container, relative)
        {
            const preference = relative ? 'relative' : 'absolute';
            const url = this.icinga.utils.addUrlParams($(container).data('icingaUrl'), {timestamps: preference});
            $(container).data('icingaUrl', url);

            [
                ['.load-more .action-link, .refresh-container-control, .primary-nav li.active a', 'href'],
                ['.search-editor-opener', 'data-search-editor-url'],
                ['.search-bar', 'action'],
            ].forEach(([selector, attr]) => {
                container.querySelectorAll(selector).forEach((el) => {
                    el.setAttribute(attr, this.icinga.utils.addUrlParams(el.getAttribute(attr), {timestamps: preference}));
                });
            });

            this.icinga.history.replaceCurrentState();

            if (relative) {
                container.querySelectorAll('.content .interactive-time').forEach(el => {
                    el.setAttribute('data-relative-time', 'ago');

                    const diff = Math.floor((
                        new Date(this.dateFormatter.format(new Date()))
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
                    el.textContent = this.timeFormatter.format(new Date(el.getAttribute('datetime')));
                });
            }
        }
    }

    Icinga.Behaviors.TimestampToggle = TimestampToggle;
}(Icinga));