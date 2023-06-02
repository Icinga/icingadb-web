/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

;(function (Icinga) {

    "use strict";

    try {
        var notjQuery = require('icinga/icinga-php-library/notjQuery');
    } catch (e) {
        console.warn('Unable to provide input enrichments. Libraries not available:', e);
        return;
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    class ActionList extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            this.on('click', '.action-list [data-action-item]:not(.page-separator), .action-list [data-action-item] a[href]', this.onClick, this);
            this.on('close-column', this.onColumnClose, this);

            this.on('rendered', '.container', this.onRendered, this);
            this.on('keydown', '#main > .container, .action-list', this.onKeyDown, this);

            this.on('click', '.load-more[data-no-icinga-ajax] a', this.onLoadMoreClick, this);
            this.on('keypress', '.load-more[data-no-icinga-ajax] a', this.onKeyPress, this);

            this.lastActivatedItemUrl = null;
            this.lastTimeoutId = null;
            this.isProcessingRequest = false;
            this.isProcessingLoadMore = false;
        }

        /**
         * Parse the filter query contained in the given URL query string
         *
         * @param {string} queryString
         *
         * @returns {array}
         */
        parseSelectionQuery(queryString) {
            return queryString.split('|');
        }

        onClick(event) {
            var _this = event.data.self;
            var $activeItems;
            var $target = $(event.currentTarget);
            var $item = $target.closest('[data-action-item]');
            var $list = $item.closest('.action-list');

            if ($target.is('a') && (! $target.is('.subject') || event.ctrlKey || event.metaKey)) {
                return true;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            event.stopPropagation();

            if ($list.is('[data-icinga-multiselect-url]')) {
                if (event.ctrlKey || event.metaKey) {
                    $item.toggleClass('active');
                } else if (event.shiftKey) {
                    document.getSelection().removeAllRanges();

                    $activeItems = $list.find('[data-action-item].active');

                    var $firstActiveItem = $activeItems.first();

                    $activeItems.removeClass('active');

                    $firstActiveItem.addClass('active');
                    $item.addClass('active');

                    if ($item.index() > $firstActiveItem.index()) {
                        $item.prevUntil($firstActiveItem).addClass('active');
                    } else {
                        var $lastActiveItem = $activeItems.last();

                        $lastActiveItem.addClass('active');
                        $item.nextUntil($lastActiveItem).addClass('active');
                    }
                } else {
                    $list.find('[data-action-item].active').removeClass('active');
                    $item.addClass('active');
                }
            } else {
                $list.find('[data-action-item].active').removeClass('active');
                $item.addClass('active');
            }

            if ($item.hasClass('active')) {
                _this.lastActivatedItemUrl = $item.data('icingaDetailFilter');
            } else {
                $activeItems = $list.find('[data-action-item].active');

                _this.lastActivatedItemUrl = $activeItems.length
                    ? $activeItems.last().data('icingaDetailFilter')
                    : null;
            }

            _this.addSelectionCountToFooter($list[0]);

            $activeItems = $list.find('[data-action-item].active');
            if ($activeItems.length === 0) {
                if (_this.icinga.loader.getLinkTargetFor($target).attr('id') === 'col2') {
                    _this.icinga.ui.layout1col();
                }
            } else {
                var url;

                if ($activeItems.length === 1) {
                    url = $target.is('a') ? $target.attr('href') : $activeItems.find('[href]').first().attr('href');
                } else {
                    var filters = $activeItems.map(function () {
                        return $(this).attr('data-icinga-multiselect-filter');
                    });

                    url = $list.attr('data-icinga-multiselect-url') + '?' + filters.toArray().join('|');
                }

                _this.icinga.loader.loadUrl(
                    url, _this.icinga.loader.getLinkTargetFor($target)
                );
            }
        }

        addSelectionCountToFooter(list) {
            if (! list.matches('[data-icinga-multiselect-url]')) {
                return;
            }

            let activeItemCount = list.querySelectorAll('[data-action-item].active').length;
            let footer = list.closest('.container').querySelector('.footer');

            // For items that do not have a bottom status bar like Downtimes, Comments...
            if (footer === null) {
                footer = notjQuery.render(
                    '<div class="footer" data-action-list-automatically-added>' +
                            '<div class="selection-count"><span class="selected-items"></span></div>' +
                        '</div>'
                )

                list.closest('.container').appendChild(footer);
            }

            let selectionCount = footer.querySelector('.selection-count');
            if (selectionCount === null) {
                selectionCount = notjQuery.render(
                    '<div class="selection-count"><span class="selected-items"></span></div>'
                );

                footer.prepend(selectionCount);
            }

            let selectedItems = selectionCount.querySelector('.selected-items');
            selectedItems.innerText = activeItemCount
                ? list.dataset.icingaMultiselectCountLabel.replace('%d', activeItemCount)
                : list.dataset.icingaMultiselectHintLabel;

            activeItemCount === 0 ? selectedItems.classList.add('hint') : selectedItems.classList.remove('hint');
        }

        onKeyDown(event) {
            let list = document.querySelector('.action-list'); // col1 if both given, intended

            if ((document.querySelector('.search-suggestions')
                && document.querySelector('.search-suggestions').hasChildNodes()) // search suggestion list is visible
                || ! list.querySelectorAll(':scope > [data-action-item]').length // no item
                || document.querySelector('#modal') // modal is open
            ) {
                return;
            }

            let _this = event.data.self;
            let activeItems = list.querySelectorAll(':scope > [data-action-item].active');
            let isMultiSelectableList = list.matches('[data-icinga-multiselect-url]');
            let url;

            if (isMultiSelectableList && (event.ctrlKey || event.metaKey) && event.keyCode === 65) { // ctrl|cmd + A
                event.preventDefault();
                let toActive = list.querySelectorAll(':scope > [data-action-item]:not(.active)');

                if (toActive.length) {
                    toActive.forEach(item => item.classList.add('active'));

                    url = _this.createMultiSelectUrl(
                        list.querySelectorAll(':scope > [data-action-item].active')
                    );

                    _this.icinga.loader.loadUrl(
                        url, _this.icinga.loader.getLinkTargetFor($(list))
                    );

                    _this.lastActivatedItemUrl = list.lastChild.dataset.icingaDetailFilter;
                    _this.addSelectionCountToFooter(list);
                }

                return;
            }

            let pressedArrowDownKey = event.key === 'ArrowDown';
            let pressedArrowUpKey = event.key === 'ArrowUp';

            if ((! pressedArrowDownKey && ! pressedArrowUpKey) || _this.isProcessingLoadMore) {
                return;
            }

            event.preventDefault();

            document.querySelector('.container[id^=col]').dataset.suspendAutorefresh = '';

            let isMultiSelect = isMultiSelectableList && event.shiftKey;
            let wasAllSelected = activeItems.length === list.querySelectorAll(':scope > [data-action-item]').length;
            let lastActivatedItem = list.querySelector(`[data-icinga-detail-filter="${ _this.lastActivatedItemUrl }"]`);
            let previousSibling = lastActivatedItem ? lastActivatedItem.previousElementSibling : null;
            let nextSibling = lastActivatedItem ? lastActivatedItem.nextElementSibling : null;
            let markAsLastActive = null; // initialized only if it is different from toActiveItem
            let toActiveItem = null;

            switch (true) {
                case ! lastActivatedItem || activeItems.length === 0:
                    toActiveItem = pressedArrowDownKey ? list.firstChild : list.lastChild;
                    // reset all on manual page refresh
                    activeItems.forEach(item => item.classList.remove('active'));
                    if (toActiveItem.classList.contains('load-more')) {
                        toActiveItem = toActiveItem.previousElementSibling;
                    }
                    break;
                case isMultiSelect && pressedArrowDownKey:
                    if (activeItems.length === 1) {
                        toActiveItem = nextSibling;
                    } else if (wasAllSelected && lastActivatedItem !== list.firstChild) {
                        toActiveItem = lastActivatedItem === list.lastChild
                            ? null
                            : list.lastChild;
                    } else if (nextSibling && nextSibling.classList.contains('active')) { // deactivate last activated by down to up select
                        lastActivatedItem.classList.remove('active');

                        toActiveItem = nextSibling;
                    } else {
                        while (nextSibling) {
                            if (! nextSibling.classList.contains('active')) {
                                break;
                            }

                            nextSibling = nextSibling.nextElementSibling;
                        }

                        toActiveItem = nextSibling;

                        // if the next sibling element is already active, mark the last active element in list as last active
                        while (nextSibling && nextSibling.nextElementSibling) {
                            if (! nextSibling.nextElementSibling.classList.contains('active')) {
                                break;
                            }

                            nextSibling = nextSibling.nextElementSibling;
                        }

                        markAsLastActive = nextSibling;
                    }

                    break;
                case isMultiSelect && pressedArrowUpKey:
                    if (activeItems.length === 1) {
                        toActiveItem = previousSibling;
                    } else if (wasAllSelected && lastActivatedItem !== list.lastChild) {
                        toActiveItem = lastActivatedItem === list.firstChild
                            ? null
                            : list.lastChild;
                    } else if (previousSibling && previousSibling.classList.contains('active')) {
                        lastActivatedItem.classList.remove('active');
                        toActiveItem = previousSibling;
                    } else {
                        while (previousSibling) {
                            if (! previousSibling.classList.contains('active')) {
                                break;
                            }

                            previousSibling = previousSibling.previousElementSibling;
                        }

                        toActiveItem = previousSibling;

                        // if the previous sibling element is already active, mark the first active element in list as last active
                        while (previousSibling && previousSibling.previousElementSibling) {
                            if (! previousSibling.previousElementSibling.classList.contains('active')) {
                                break;
                            }

                            previousSibling = previousSibling.previousElementSibling;
                        }

                        markAsLastActive = previousSibling;
                    }

                    break;
                case pressedArrowDownKey:
                case pressedArrowUpKey:
                    toActiveItem = pressedArrowDownKey
                        ? nextSibling ?? lastActivatedItem
                        : previousSibling ?? lastActivatedItem;

                    if (wasAllSelected && pressedArrowUpKey) {
                        toActiveItem = previousSibling ?? lastActivatedItem
                    }

                    if (toActiveItem && ! toActiveItem.classList.contains('load-more')) {
                        activeItems.forEach(item => item.classList.remove('active'));

                        if (toActiveItem.classList.contains('page-separator')) {
                            toActiveItem = pressedArrowDownKey
                                ? toActiveItem.nextElementSibling
                                : toActiveItem.previousElementSibling;
                        }
                    }

                    break;
            }

            // $currentActiveItems already contain the first/last element of the list and have no prev/next element
            if (! toActiveItem) {
                return;
            }

            if (toActiveItem.classList.contains('load-more')) {
                let req = _this.loadMore($(toActiveItem.querySelector('a')));
                _this.isProcessingLoadMore = true;
                req.done(function (toActiveItem) {
                    _this.isProcessingLoadMore = false;
                    // list has now new items, so select the lastActivatedItem and then move forward
                    toActiveItem = list.querySelector(`[data-icinga-detail-filter="${ lastActivatedItem.dataset.icingaDetailFilter }"]`);
                    toActiveItem = toActiveItem.nextElementSibling;
                    while (toActiveItem) {
                        if (toActiveItem.hasAttribute('data-action-item')) {
                            activeItems.forEach(item => item.classList.remove('active'));
                            _this.setActive(toActiveItem);
                            return;
                        }

                        toActiveItem = toActiveItem.nextElementSibling;
                    }
                });
            } else {
                _this.setActive(toActiveItem, markAsLastActive);
            }
        }

        setActive(toActiveItem, markAsLastActive) {
            toActiveItem.classList.add('active');
            this.lastActivatedItemUrl = markAsLastActive
                ? markAsLastActive.dataset.icingaDetailFilter
                : toActiveItem.dataset.icingaDetailFilter;

            let list = document.querySelector('.action-list');
            let activeItems = list.querySelectorAll(':scope > [data-action-item].active');

            let url = null;
            if (activeItems.length > 1) {
                url = this.createMultiSelectUrl(activeItems);
            } else {
                url = toActiveItem.querySelector('[href]').getAttribute('href');
            }

            this.addSelectionCountToFooter(list);

            this.isProcessingRequest = true;
            clearTimeout(this.lastTimeoutId);
            this.lastTimeoutId = setTimeout(() => {
                let req = this.icinga.loader.loadUrl(url, this.icinga.loader.getLinkTargetFor($(toActiveItem)));
                req.done(() => {
                    this.isProcessingRequest = false;
                    delete document.querySelector('.container[id^=col]').dataset.suspendAutorefresh;
                });
            }, 250);
        }

        onLoadMoreClick(event) {
            event.stopPropagation();
            event.preventDefault();

            event.data.self.loadMore($(event.target));

            return false;
        }

        onKeyPress(event) {
            if (event.key === ' ') { // space
                event.data.self.onLoadMoreClick(event);
            }
        }

        loadMore($anchor) {
            var $loadMore = $anchor.parent();
            var progressTimer = this.icinga.timer.register(function () {
                var label = $anchor.html();

                var dots = label.substr(-3);
                if (dots.slice(0, 1) !== '.') {
                    dots = '.  ';
                } else {
                    label = label.slice(0, -3);
                    if (dots === '...') {
                        dots = '.  ';
                    } else if (dots === '.. ') {
                        dots = '...';
                    } else if (dots === '.  ') {
                        dots = '.. ';
                    }
                }

                $anchor.html(label + dots);
            }, null, 250);

            var url = $anchor.attr('href');
            var req = this.icinga.loader.loadUrl(
                // Add showCompact, we don't want controls in paged results
                this.icinga.utils.addUrlFlag(url, 'showCompact'),
                $loadMore.parent(),
                undefined,
                undefined,
                'append',
                false,
                progressTimer
            );
            req.addToHistory = false;
            req.done(function () {
                $loadMore.remove();

                // Set data-icinga-url to make it available for Icinga.History.getCurrentState()
                req.$target.closest('.container').data('icingaUrl', url);

                this.icinga.history.replaceCurrentState();
            });

            return req;
        }

        createMultiSelectUrl(items) {
            let filters = [];
            items.forEach(item => {
                filters.push(item.getAttribute('data-icinga-multiselect-filter'));
            });

            return items[0].parentElement.getAttribute('data-icinga-multiselect-url') + '?' + filters.join('|');
        }

        onColumnClose(event) {
            var $target = $(event.target);

            if ($target.attr('id') !== 'col2') {
                return;
            }

            var $list = $('#col1').find('.action-list');
            if ($list.length && $list.is('[data-icinga-multiselect-url]')) {
                var _this = event.data.self;
                var detailUrl = _this.icinga.utils.parseUrl(_this.icinga.history.getCol2State().replace(/^#!/, ''));

                if ($list.attr('data-icinga-multiselect-url') === detailUrl.path) {
                    $.each(_this.parseSelectionQuery(detailUrl.query.slice(1)), function (i, filter) {
                        $list.find(
                            '[data-icinga-multiselect-filter="' + filter + '"]'
                        ).removeClass('active');
                    });
                } else if (_this.matchesDetailUrl($list.attr('data-icinga-detail-url'), detailUrl.path)) {
                    $list.find(
                        '[data-icinga-detail-filter="' + detailUrl.query.slice(1) + '"]'
                    ).removeClass('active');
                }

                _this.addSelectionCountToFooter($list[0]);
            }
        }

        onRendered(event) {
            let _this = event.data.self;
            let container = event.target;
            let isTopLevelContainer = container.matches('#main > :scope');

            if (event.currentTarget !== container || _this.isProcessingRequest) {
                // Nested containers are not processed multiple times || still processing selection/navigation request
                return;
            } else if (isTopLevelContainer && container.id !== 'col1') {
                return;
            }

            let list = container.querySelector('.action-list');

            if (list && list.matches('[data-icinga-multiselect-url], [data-icinga-detail-url]')) {
                let detailUrl = _this.icinga.utils.parseUrl(_this.icinga.history.getCol2State().replace(/^#!/, ''));

                if (list.dataset.icingaMultiselectUrl === detailUrl.path) {
                    for (const filter of _this.parseSelectionQuery(detailUrl.query.slice(1))) {
                        let item = list.querySelector('[data-icinga-multiselect-filter="' + filter + '"]');
                        if (item) {
                            item.classList.add('active');
                        }
                    }
                } else if (_this.matchesDetailUrl(list.dataset.icingaDetailUrl, detailUrl.path)) {
                    let item = list.querySelector('[data-icinga-detail-filter="' + detailUrl.query.slice(1) + '"]');
                    if (item) {
                        item.classList.add('active');
                    }
                }

                _this.addSelectionCountToFooter(list);
            }
        }

        matchesDetailUrl(itemUrl, detailUrl) {
            if (itemUrl === detailUrl) {
                return true;
            }

            // The slash is used to avoid false positives (e.g. icingadb/hostgroup and icingadb/host)
            return detailUrl.startsWith(itemUrl + '/');
        }
    }

    Icinga.Behaviors.ActionList = ActionList;

}(Icinga));
