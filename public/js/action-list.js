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
            this.on('column-moved', this.onColumnMoved, this);

            this.on('rendered', '.container', this.onRendered, this);
            this.on('keydown', '#body', this.onKeyDown, this);

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

            $activeItems = $list.find('[data-action-item].active');
            if ($item.hasClass('active')) {
                _this.setLastActivatedItemUrl($item.data('icingaDetailFilter'));
            } else {
                _this.setLastActivatedItemUrl(
                    $activeItems.length
                    ? $activeItems.last().data('icingaDetailFilter')
                    : null
                );
            }

            _this.addSelectionCountToFooter($list[0]);

            if ($activeItems.length === 0) {
                if (_this.icinga.loader.getLinkTargetFor($target).attr('id') === 'col2') {
                    _this.icinga.ui.layout1col();
                }
            } else {
                _this.loadDetailUrl(
                    $list[0],
                    $target.is('a') ? $target.attr('href') : null
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

            if (activeItemCount === 0) {
                selectedItems.classList.add('hint');
            } else {
                selectedItems.classList.remove('hint');
            }
        }

        onKeyDown(event) {
            let _this = event.data.self;
            let list = null;
            let pressedArrowDownKey = event.key === 'ArrowDown';
            let pressedArrowUpKey = event.key === 'ArrowUp';
            let focusedElement = document.activeElement;

            if (_this.isProcessingLoadMore || (
                event.key.toLowerCase() !== 'a' && ! pressedArrowDownKey && ! pressedArrowUpKey
            )) {
                return;
            }

            if (focusedElement && (
                focusedElement.matches('#main > :scope')
                || focusedElement.matches('#body'))
            ) {
                let activeItem = document.querySelector(
                    '#main > .container > .content > .action-list > .active'
                );
                if (activeItem) {
                    list = activeItem.parentElement;
                } else {
                    list = focusedElement.querySelector('#main > .container > .content > .action-list');
                }
            } else if (focusedElement) {
                list = focusedElement.closest('.content > .action-list');
            }

            if (! list) {
                return;
            }

            let listItemsLength = list.querySelectorAll(':scope > [data-action-item]').length;
            let activeItems = list.querySelectorAll(':scope > [data-action-item].active');
            let isMultiSelectableList = list.matches('[data-icinga-multiselect-url]');

            if (isMultiSelectableList && (event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'a') {
                event.preventDefault();
                _this.selectAll(list);
                return;
            }

            event.preventDefault();

            list.closest('#main > .container').dataset.suspendAutorefresh = '';

            let wasAllSelected = activeItems.length === listItemsLength;
            let lastActivatedItem = list.querySelector(
                `[data-icinga-detail-filter="${ _this.lastActivatedItemUrl }"]`
            );
            let directionalNextItem = _this.getDirectionalNext(lastActivatedItem, event.key);
            let markAsLastActive = null; // initialized only if it is different from toActiveItem
            let toActiveItem = null;


            if (! lastActivatedItem || activeItems.length === 0) {
                toActiveItem = pressedArrowDownKey ? list.firstChild : list.lastChild;
                // reset all on manual page refresh
                _this.clearSelection(activeItems);
                if (toActiveItem.classList.contains('load-more')) {
                    toActiveItem = toActiveItem.previousElementSibling;
                }
            } else if (isMultiSelectableList && event.shiftKey) {
                if (activeItems.length === 1) {
                    toActiveItem = directionalNextItem;
                } else if (wasAllSelected && (
                    (lastActivatedItem !== list.firstChild && pressedArrowDownKey)
                    || (lastActivatedItem !== list.lastChild && pressedArrowUpKey)
                )) {
                    if (pressedArrowDownKey) {
                        toActiveItem = lastActivatedItem === list.lastChild ? null : list.lastChild;
                    } else {
                        toActiveItem = lastActivatedItem === list.firstChild ? null : list.lastChild;
                    }

                } else if (directionalNextItem && directionalNextItem.classList.contains('active')) {
                    // deactivate last activated by down to up select
                    _this.clearSelection([lastActivatedItem]);
                    if (wasAllSelected) {
                        _this.scrollItemIntoView(lastActivatedItem, event.key);
                    }

                    toActiveItem = directionalNextItem;
                } else {
                    [toActiveItem, markAsLastActive] = _this.findToActiveItem(lastActivatedItem, event.key);
                }
            } else {
                toActiveItem = directionalNextItem ?? lastActivatedItem;

                if (toActiveItem) {
                    if (toActiveItem.classList.contains('load-more')) {
                        _this.handleLoadMoreNavigate(toActiveItem, lastActivatedItem, event.key);
                        return;
                    }

                    _this.clearSelection(activeItems);
                    if (toActiveItem.classList.contains('page-separator')) {
                        toActiveItem = _this.getDirectionalNext(toActiveItem, event.key);
                    }
                }
            }

            if (! toActiveItem) {
                return;
            }

            _this.setActive(toActiveItem);
            _this.setLastActivatedItemUrl(
                markAsLastActive ? markAsLastActive.dataset.icingaDetailFilter : toActiveItem.dataset.icingaDetailFilter
            );
            _this.scrollItemIntoView(toActiveItem, event.key);
            _this.addSelectionCountToFooter(list);
            _this.loadDetailUrl(list);
        }

        getDirectionalNext(item, eventKey) {
            if (! item) {
                return null;
            }

            return eventKey === 'ArrowUp' ? item.previousElementSibling : item.nextElementSibling;
        }

        findToActiveItem(lastActivatedItem, eventKey) {
            let toActiveItem;
            let markAsLastActive;

            toActiveItem = this.getDirectionalNext(lastActivatedItem, eventKey);

            while (toActiveItem) {
                if (! toActiveItem.classList.contains('active')) {
                    break;
                }

                toActiveItem = this.getDirectionalNext(toActiveItem, eventKey);
            }

            markAsLastActive = toActiveItem;
            // if the next/previous sibling element is already active,
            // mark the last/first active element in list as last active
            while (markAsLastActive && this.getDirectionalNext(markAsLastActive, eventKey)) {
                if (! this.getDirectionalNext(markAsLastActive, eventKey).classList.contains('active')) {
                    break;
                }

                markAsLastActive = this.getDirectionalNext(markAsLastActive, eventKey);
            }

            return [toActiveItem, markAsLastActive];
        }

        selectAll(list) {
            this.setActive(list.querySelectorAll(':scope > [data-action-item]:not(.active)'));
            this.setLastActivatedItemUrl(list.lastChild.dataset.icingaDetailFilter);
            this.addSelectionCountToFooter(list);
            this.loadDetailUrl(list);
        }

        clearSelection(selectedItems) {
            selectedItems.forEach(item => item.classList.remove('active'));
        }

        setLastActivatedItemUrl (url) {
            this.lastActivatedItemUrl = url;
        }

        scrollItemIntoView(item, pressedKey) {
            item.scrollIntoView({block: "nearest"});

            let directionalNext = this.getDirectionalNext(item, pressedKey);

            if (directionalNext) {
                directionalNext.scrollIntoView({block: "nearest"});
            }
        }

        loadDetailUrl(list, anchorUrl = null) {
            let url = anchorUrl;
            if (url === null) {
                let activeItems = list.querySelectorAll(':scope > [data-action-item].active');

                if (activeItems.length > 1) {
                    url = this.createMultiSelectUrl(activeItems);
                } else {
                    url = activeItems[0].querySelector('[href]').getAttribute('href');
                }
            }

            this.isProcessingRequest = true;
            clearTimeout(this.lastTimeoutId);
            this.lastTimeoutId = setTimeout(() => {
                let req = this.icinga.loader.loadUrl(url, this.icinga.loader.getLinkTargetFor($(list)));
                this.lastTimeoutId = null;
                req.done(() => {
                    this.isProcessingRequest = false;
                    let container = list.closest('#main > .container');
                    if (container) { // avoid call on null error
                        delete container.dataset.suspendAutorefresh;
                    }
                });
            }, 250);
        }

        setActive(toActiveItem) {
            if (toActiveItem instanceof HTMLElement) {
                toActiveItem.classList.add('active');
            } else {
                toActiveItem.forEach(item => item.classList.add('active'));
            }
        }

        handleLoadMoreNavigate(loadMoreElement, lastActivatedItem, pressedKey) {
            let req = this.loadMore($(loadMoreElement.querySelector('a')));
            this.isProcessingLoadMore = true;
            req.done(() => {
                this.isProcessingLoadMore = false;
                // list has now new items, so select the lastActivatedItem and then move forward
                let toActiveItem = lastActivatedItem.nextElementSibling;
                while (toActiveItem) {
                    if (toActiveItem.hasAttribute('data-action-item')) {
                        this.clearSelection([lastActivatedItem]);
                        this.setActive(toActiveItem);
                        this.setLastActivatedItemUrl(toActiveItem.dataset.icingaDetailFilter);
                        this.scrollItemIntoView(toActiveItem, pressedKey);
                        this.addSelectionCountToFooter(toActiveItem.parentElement);
                        this.loadDetailUrl(toActiveItem.parentElement);
                        return;
                    }

                    toActiveItem = toActiveItem.nextElementSibling;
                }
            });
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

            return items[0].parentElement.getAttribute('data-icinga-multiselect-url')
                + '?'
                + filters.join('|');
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

        onColumnMoved (event, sourceId) {
            let _this = event.data.self;

            if (event.target.id === 'col2' && sourceId === 'col1') { // only for browser-back (col1 shifted to col2)
                _this.clearSelection(event.target.querySelectorAll('.action-list .active'));
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
                let detailUrl = _this.icinga.utils.parseUrl(
                    _this.icinga.history.getCol2State().replace(/^#!/, '')
                );

                let item = null;
                if (list.dataset.icingaMultiselectUrl === detailUrl.path) {
                    for (const filter of _this.parseSelectionQuery(detailUrl.query.slice(1))) {
                        item = list.querySelector('[data-icinga-multiselect-filter="' + filter + '"]');
                        if (item) {
                            item.classList.add('active');
                        }
                    }
                } else if (_this.matchesDetailUrl(list.dataset.icingaDetailUrl, detailUrl.path)) {
                    item = list.querySelector('[data-icinga-detail-filter="' + detailUrl.query.slice(1) + '"]');
                    if (item) {
                        item.classList.add('active');
                    }
                }

                if (isTopLevelContainer) {
                    _this.addSelectionCountToFooter(list);
                }
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
