// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

(function (Icinga) {

    "use strict";

    class TimestampToggle extends Icinga.EventListener {
        constructor(icinga)
        {
            super(icinga);

            this.on('change', '.icingadb-timestamp-toggle', this.onTimestampModeToggle, this);
            this.on('click', '.icingadb-history-timestamp', this.onTimestampClick, this);
        }

        /**
         * Toggle the timestamp mode of the container
         *
         * This method stops event propagation, so that the list-item underneath is not clicked.
         *
         * @param event The click event of the timestamp
         */
        onTimestampClick(event)
        {
            event.stopPropagation();

            const container = event.target.closest('.container');
            const toggle = container.querySelector('.icingadb-timestamp-toggle');

            if (toggle) {
                toggle.click();
            } else {
                event.data.self.applyTimestampMode(container, ! event.target.hasAttribute('data-relative-time'));
            }
        }

        /**
         * Handle changed timestamp mode
         *
         * @param event The change event of the icingadb-timestamp-toggle
         */
        onTimestampModeToggle(event)
        {
            event.data.self.applyTimestampMode(event.target.closest('.container'), event.target.checked);
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
            this.icinga.history.replaceCurrentState();

            Object.entries({
                '.load-more .action-link, .refresh-container-control, .primary-nav li.active a': 'href',
                '.search-editor-opener': 'data-search-editor-url',
                '.search-bar': 'action'
            }).forEach(([selector, attr]) => {
                container.querySelectorAll(selector).forEach((el) => {
                    el.setAttribute(attr, this.icinga.utils.addUrlParams(el.getAttribute(attr), {timestamps: preference}));
                });
            });

            container.querySelectorAll('.item-list .icingadb-history-timestamp').forEach(el => {
                el.hidden = ! el.hidden;
            });
        }
    }

    Icinga.Behaviors.TimestampToggle = TimestampToggle;
}(Icinga));
