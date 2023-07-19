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
            this.on('close-column', '#main > #col2', this.onColumnClose, this);
            this.on('column-moved', this.onColumnMoved, this);

            this.on('rendered', '#main > .container', this.onRendered, this);
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
            let _this = event.data.self;
            let target = event.currentTarget;

            if (target.matches('a') && (! target.matches('.subject') || event.ctrlKey || event.metaKey)) {
                return true;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            event.stopPropagation();

            let item = target.closest('[data-action-item]');
            let list = target.closest('.action-list');
            let activeItems = Array.from(list.querySelectorAll(':scope > [data-action-item].active'));
            let toActiveItems = [],
                toDeactivateItems = [];

            list.closest('#main > .container').dataset.suspendAutorefresh = '';

            if (list.matches('[data-icinga-multiselect-url]') && (event.ctrlKey || event.metaKey || event.shiftKey)) {
                if (event.ctrlKey || event.metaKey) {
                    if (item.classList.contains('active')) {
                        toDeactivateItems.push(item);
                    } else {
                        toActiveItems.push(item);
                    }
                } else {
                    document.getSelection().removeAllRanges();

                    let allItems = Array.from(list.querySelectorAll(':scope > [data-action-item]'));

                    let startIndex = allItems.indexOf(item);
                    if(startIndex < 0) {
                        startIndex = 0;
                    }

                    let endIndex = activeItems.length ? allItems.indexOf(activeItems[0]) : 0;
                    if (startIndex > endIndex) {
                        toActiveItems = allItems.slice(endIndex, startIndex + 1);
                    } else {
                        endIndex = activeItems.length ? allItems.indexOf(activeItems[activeItems.length - 1]) : 0;
                        toActiveItems = allItems.slice(startIndex, endIndex + 1);
                    }

                    toDeactivateItems = activeItems.filter(item => ! toActiveItems.includes(item));
                    toActiveItems = toActiveItems.filter(item => ! activeItems.includes(item));
                }
            } else {
                toDeactivateItems = activeItems;
                toActiveItems.push(item);
            }

            if (activeItems.length === 1
                && toActiveItems.length === 0
                && _this.icinga.loader.getLinkTargetFor($(target)).attr('id') === 'col2'
            ) {
                _this.icinga.ui.layout1col();
                return;
            }

            let lastActivatedUrl = null;
            if (toActiveItems.includes(item)) {
                lastActivatedUrl = item.dataset.icingaDetailFilter;
            } else if (activeItems.length > 1) {
                lastActivatedUrl = activeItems[activeItems.length - 1] === item
                    ? activeItems[activeItems.length - 2].dataset.icingaDetailFilter
                    : activeItems[activeItems.length - 1].dataset.icingaDetailFilter;
            }

            _this.clearSelection(toDeactivateItems);
            _this.setActive(toActiveItems);
            _this.addSelectionCountToFooter(list);
            _this.setLastActivatedItemUrl(lastActivatedUrl);
            _this.loadDetailUrl(list, target.matches('a') ? target.href : null);
        }

        /**
         * Add the selection count to footer if list allow multi selection
         *
         * @param list
         */
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

        /**
         * Key navigation for .action-list
         *
         * - `Shift + ArrowUp|ArrowDown` = Multiselect
         * - `ArrowUp|ArrowDown` = Select next/previous
         * - `Ctrl|cmd + A` = Select all on currect page
         *
         * @param event
         */
        onKeyDown(event) {
            let _this = event.data.self;
            let list = null;
            let pressedArrowDownKey = event.key === 'ArrowDown';
            let pressedArrowUpKey = event.key === 'ArrowUp';
            let focusedElement = document.activeElement;

            if (
                _this.isProcessingLoadMore
                || ! event.key // input auto-completion is triggered
                || (event.key.toLowerCase() !== 'a' && ! pressedArrowDownKey && ! pressedArrowUpKey)
            ) {
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

            let markAsLastActive = null; // initialized only if it is different from toActiveItem
            let toActiveItem = null;
            let wasAllSelected = activeItems.length === listItemsLength;
            let lastActivatedItem = list.querySelector(
                `[data-icinga-detail-filter="${ _this.lastActivatedItemUrl }"]`
            );

            if (! lastActivatedItem && activeItems.length) {
                lastActivatedItem = activeItems[activeItems.length - 1];
            }

            let directionalNextItem = _this.getDirectionalNext(lastActivatedItem, event.key);

            if (activeItems.length === 0) {
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
                        clearTimeout(_this.lastTimeoutId);
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

        /**
         * Get the next list item according to the pressed key (`ArrowUp` or `ArrowDown`)
         *
         * @param item The list item from which we want the next item
         * @param eventKey Pressed key (`ArrowUp` or `ArrowDown`)
         *
         * @returns {Element|null}
         */
        getDirectionalNext(item, eventKey) {
            if (! item) {
                return null;
            }

            return eventKey === 'ArrowUp' ? item.previousElementSibling : item.nextElementSibling;
        }

        /**
         * Find the list item that should be activated next
         *
         * @param lastActivatedItem
         * @param eventKey Pressed key (`ArrowUp` or `ArrowDown`)
         *
         * @returns {Element[]}
         */
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

        /**
         * Select All list items
         *
         * @param list The action list
         */
        selectAll(list) {
            this.setActive(list.querySelectorAll(':scope > [data-action-item]:not(.active)'));
            this.setLastActivatedItemUrl(list.lastChild.dataset.icingaDetailFilter);
            this.addSelectionCountToFooter(list);
            this.loadDetailUrl(list);
        }

        /**
         * Clear the selection by removing .active class
         *
         * @param selectedItems The items with class active
         */
        clearSelection(selectedItems) {
            selectedItems.forEach(item => item.classList.remove('active'));
        }

        /**
         * Set the last activated item Url
         *
         * @param url
         */
        setLastActivatedItemUrl (url) {
            this.lastActivatedItemUrl = url;
        }

        /**
         * Scroll the given item into view
         *
         * @param item Item to scroll into view
         * @param pressedKey Pressed key (`ArrowUp` or `ArrowDown`)
         */
        scrollItemIntoView(item, pressedKey) {
            item.scrollIntoView({block: "nearest"});
            let directionalNext = this.getDirectionalNext(item, pressedKey);

            if (directionalNext) {
                directionalNext.scrollIntoView({block: "nearest"});
            }
        }

        /**
         * Load the detail url with selected items
         *
         * @param list The action list
         * @param anchorUrl If any anchor is clicked (e.g. host in service list)
         */
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

        /**
         * Add .active class to given list item
         *
         * @param toActiveItem The list item(s)
         */
        setActive(toActiveItem) {
            if (toActiveItem instanceof HTMLElement) {
                toActiveItem.classList.add('active');
            } else {
                toActiveItem.forEach(item => item.classList.add('active'));
            }
        }

        /**
         * Handle the navigation on load-more button
         *
         * @param loadMoreElement
         * @param lastActivatedItem
         * @param pressedKey Pressed key (`ArrowUp` or `ArrowDown`)
         */
        handleLoadMoreNavigate(loadMoreElement, lastActivatedItem, pressedKey) {
            let req = this.loadMore(loadMoreElement.firstChild);
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

        /**
         * Click on load-more button
         *
         * @param event
         *
         * @returns {boolean}
         */
        onLoadMoreClick(event) {
            event.stopPropagation();
            event.preventDefault();

            event.data.self.loadMore(event.target);

            return false;
        }

        onKeyPress(event) {
            if (event.key === ' ') { // space
                event.data.self.onLoadMoreClick(event);
            }
        }

        /**
         * Load more list items based on the given anchor
         *
         * @param anchor
         *
         * @returns {*|{getAllResponseHeaders: function(): *|null, abort: function(*): this, setRequestHeader: function(*, *): this, readyState: number, getResponseHeader: function(*): null|*, overrideMimeType: function(*): this, statusCode: function(*): this}|jQuery|boolean}
         */
        loadMore(anchor) {
            let showMore = anchor.parentElement;
            var progressTimer = this.icinga.timer.register(function () {
                var label = anchor.innerText;

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

                anchor.innerText = label + dots;
            }, null, 250);

            let url = anchor.href;
            let req = this.icinga.loader.loadUrl(
                // Add showCompact, we don't want controls in paged results
                this.icinga.utils.addUrlFlag(url, 'showCompact'),
                $(showMore.parentElement),
                undefined,
                undefined,
                'append',
                false,
                progressTimer
            );
            req.addToHistory = false;
            req.done(function () {
                showMore.remove();

                // Set data-icinga-url to make it available for Icinga.History.getCurrentState()
                req.$target.closest('.container').data('icingaUrl', url);

                this.icinga.history.replaceCurrentState();
            });

            return req;
        }

        /**
         * Create the detail url for multi selectable list
         *
         * @param items List items
         * @param withBaseUrl Default to true
         *
         * @returns {string} The url
         */
        createMultiSelectUrl(items, withBaseUrl = true) {
            let filters = [];
            items.forEach(item => {
                filters.push(item.getAttribute('data-icinga-multiselect-filter'));
            });

            let url = '?' + filters.join('|');

            if (withBaseUrl) {
                return items[0].parentElement.getAttribute('data-icinga-multiselect-url') + url;
            }

            return url;
        }

        onColumnClose(event) {
            let _this = event.data.self;
            let list = _this.findDetailUrlActionList();
            if (list && list.matches('[data-icinga-multiselect-url], [data-icinga-detail-url]')) {
                _this.clearSelection(list.querySelectorAll(':scope > [data-action-item].active'));
                _this.addSelectionCountToFooter(list);
            }
        }

        /**
         * Find the action list using the detail url
         *
         * @return Element|null
         */
        findDetailUrlActionList() {
            let detailUrl = this.icinga.utils.parseUrl(
                this.icinga.history.getCol2State().replace(/^#!/, '')
            );

            let detailItem = document.querySelector(
                '#main > .container .action-list > li[data-icinga-detail-filter="'
                + detailUrl.query.replace('?', '') + '"],' +
                '#main > .container .action-list > li[data-icinga-multiselect-filter="'
                + detailUrl.query.split('|', 1).toString().replace('?', '') + '"]'
            );

            return detailItem ? detailItem.parentElement : null;
        }

        /**
         * Triggers when column is moved to left or right
         *
         * @param event
         * @param sourceId The content is moved from
         */
        onColumnMoved (event, sourceId) {
            let _this = event.data.self;

            if (event.target.id === 'col2' && sourceId === 'col1') { // only for browser-back (col1 shifted to col2)
                _this.clearSelection(event.target.querySelectorAll('.action-list .active'));
            }
        }

        onRendered(event, isAutoRefresh) {
            let _this = event.data.self;
            let container = event.target;
            let isTopLevelContainer = container.matches('#main > :scope');

            if (event.currentTarget !== container || _this.isProcessingRequest) {
                // Nested containers are not processed multiple times || still processing selection/navigation request
                return;
            } else if (isAutoRefresh && isTopLevelContainer && container.id !== 'col1') {
                return;
            }

            let list = _this.findDetailUrlActionList();

            if (list && list.matches('[data-icinga-multiselect-url], [data-icinga-detail-url]')) {
                let detailUrl = _this.icinga.utils.parseUrl(
                    _this.icinga.history.getCol2State().replace(/^#!/, '')
                );
                let toActiveItems = [];
                if (list.dataset.icingaMultiselectUrl === detailUrl.path) {
                    for (const filter of _this.parseSelectionQuery(detailUrl.query.slice(1))) {
                        let item = list.querySelector(
                            '[data-icinga-multiselect-filter="' + filter + '"]'
                        );

                        if (item) {
                            toActiveItems.push(item);
                        }
                    }
                } else if (_this.matchesDetailUrl(list.dataset.icingaDetailUrl, detailUrl.path)) {
                    let item = list.querySelector(
                        '[data-icinga-detail-filter="' + detailUrl.query.slice(1) + '"]'
                    );

                    if (item) {
                        toActiveItems.push(item);
                    }
                }

                let allItems = Array.from(list.querySelectorAll(':scope > [data-action-item]'));
                _this.clearSelection(allItems.filter(item => ! toActiveItems.includes(item)));
                _this.setActive(toActiveItems);

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
