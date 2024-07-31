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

            this.on('rendered', '#main .container', this.onRendered, this);
            this.on('keydown', '#body', this.onKeyDown, this);

            this.on('click', '.load-more[data-no-icinga-ajax] a', this.onLoadMoreClick, this);
            this.on('keypress', '.load-more[data-no-icinga-ajax] a', this.onKeyPress, this);

            this.lastActivatedItemUrl = null;
            this.lastTimeoutId = null;
            this.isProcessingLoadMore = false;
            this.activeRequests = {};
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

        /**
         * Suspend auto refresh for the given item's container
         *
         * @param {Element} item
         *
         * @return {string} The container's id
         */
        suspendAutoRefresh(item) {
            const container = item.closest('.container');
            container.dataset.suspendAutorefresh = '';

            return container.id;
        }

        /**
         * Enable auto refresh on the given container
         *
         * @param {string} containerId
         */
        enableAutoRefresh(containerId) {
            delete document.getElementById(containerId).dataset.suspendAutorefresh;
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
            let activeItems = _this.getActiveItems(list);
            let toActiveItems = [],
                toDeactivateItems = [];

            const isBeingMultiSelected = list.matches('[data-icinga-multiselect-url]')
                && (event.ctrlKey || event.metaKey || event.shiftKey);

            if (isBeingMultiSelected) {
                if (event.ctrlKey || event.metaKey) {
                    if (item.classList.contains('active')) {
                        toDeactivateItems.push(item);
                    } else {
                        toActiveItems.push(item);
                    }
                } else {
                    document.getSelection().removeAllRanges();

                    let allItems = _this.getAllItems(list);

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
                _this.icinga.history.pushCurrentState();
                _this.enableAutoRefresh('col1');
                return;
            }

            let dashboard = list.closest('.dashboard');
            if (dashboard) {
                _this.clearDashboardSelections(dashboard, list);
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
            _this.loadDetailUrl(list, target.matches('a') ? target.getAttribute('href') : null);
        }

        /**
         * Add the selection count to footer if list allow multi selection
         *
         * @param list
         */
        addSelectionCountToFooter(list) {
            if (! list.matches('[data-icinga-multiselect-url]') || list.closest('.dashboard')) {
                return;
            }

            let activeItemCount = this.getActiveItems(list).length;
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
         * Only for primary lists (dashboard or lists in detail view are not taken into account)
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
                    '#main > .container > .content > .action-list [data-action-item].active'
                );
                if (activeItem) {
                    list = activeItem.closest('.action-list');
                } else {
                    list = focusedElement.querySelector('#main > .container > .content > .action-list');
                }
            } else if (focusedElement) {
                list = focusedElement.closest('.content > .action-list');
            }

            if (! list) {
                return;
            }

            let isMultiSelectableList = list.matches('[data-icinga-multiselect-url]');

            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'a') {
                if (! isMultiSelectableList) {
                    return;
                }

                event.preventDefault();
                _this.selectAll(list);
                return;
            }

            event.preventDefault();

            let allItems = _this.getAllItems(list);
            let firstListItem = allItems[0];
            let lastListItem = allItems[allItems.length -1];
            let activeItems = _this.getActiveItems(list);
            let markAsLastActive = null; // initialized only if it is different from toActiveItem
            let toActiveItem = null;
            let wasAllSelected = activeItems.length === allItems.length;
            let lastActivatedItem = list.querySelector(
                `[data-icinga-detail-filter="${ _this.lastActivatedItemUrl }"]`
            );

            if (! lastActivatedItem && activeItems.length) {
                lastActivatedItem = activeItems[activeItems.length - 1];
            }

            let directionalNextItem = _this.getDirectionalNext(lastActivatedItem, event.key);

            if (activeItems.length === 0) {
                toActiveItem = pressedArrowDownKey ? firstListItem : lastListItem;
                // reset all on manual page refresh
                _this.clearSelection(activeItems);
                if (toActiveItem.classList.contains('load-more')) {
                    toActiveItem = toActiveItem.previousElementSibling;
                }
            } else if (isMultiSelectableList && event.shiftKey) {
                if (activeItems.length === 1) {
                    toActiveItem = directionalNextItem;
                } else if (wasAllSelected && (
                    (lastActivatedItem !== firstListItem && pressedArrowDownKey)
                    || (lastActivatedItem !== lastListItem && pressedArrowUpKey)
                )) {
                    if (pressedArrowDownKey) {
                        toActiveItem = lastActivatedItem === lastListItem ? null : lastListItem;
                    } else {
                        toActiveItem = lastActivatedItem === firstListItem ? null : lastListItem;
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
            let allItems = this.getAllItems(list);
            let activeItems = this.getActiveItems(list);
            this.setActive(allItems.filter(item => ! activeItems.includes(item)));
            this.setLastActivatedItemUrl(allItems[allItems.length -1].dataset.icingaDetailFilter);
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
            let directionalNext = this.getDirectionalNext(item, pressedKey);

            if ("isDisplayContents" in item.parentElement.dataset) {
                item = item.firstChild;
                directionalNext = directionalNext ? directionalNext.firstChild : null;
            }
            // required when ArrowUp is pressed in new list OR after selecting all items with ctrl+A
            item.scrollIntoView({block: "nearest"});

            if (directionalNext) {
                directionalNext.scrollIntoView({block: "nearest"});
            }
        }

        clearDashboardSelections(dashboard, currentList) {
            dashboard.querySelectorAll('.action-list').forEach(otherList => {
                if (otherList !== currentList) {
                    this.clearSelection(this.getActiveItems(otherList));
                }
            })
        }

        /**
         * Load the detail url with selected items
         *
         * @param list The action list
         * @param anchorUrl If any anchor is clicked (e.g. host in service list)
         */
        loadDetailUrl(list, anchorUrl = null) {
            let url = anchorUrl;
            let activeItems = this.getActiveItems(list);

            if (url === null) {
                if (activeItems.length > 1) {
                    url = this.createMultiSelectUrl(activeItems);
                } else {
                    let anchor = activeItems[0].querySelector('[href]');
                    url = anchor ? anchor.getAttribute('href') : null;
                }
            }

            if (url === null) {
                return;
            }

            const suspendedContainer = this.suspendAutoRefresh(list);

            clearTimeout(this.lastTimeoutId);
            this.lastTimeoutId = setTimeout(() => {
                const requestNo = this.lastTimeoutId;
                this.activeRequests[requestNo] = suspendedContainer;
                this.lastTimeoutId = null;

                let req = this.icinga.loader.loadUrl(
                    url,
                    this.icinga.loader.getLinkTargetFor($(activeItems[0]))
                );

                req.always((_, __, errorThrown) => {
                    if (errorThrown !== 'abort') {
                        this.enableAutoRefresh(this.activeRequests[requestNo]);
                    }

                    delete this.activeRequests[requestNo];
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
         * Get the active items from given list
         *
         * @param list The action list
         *
         * @return array
         */
        getActiveItems(list)
        {
            let items;
            if (list.tagName.toLowerCase() === 'table') {
                items = list.querySelectorAll(':scope > tbody > [data-action-item].active');
            } else {
                items = list.querySelectorAll(':scope > [data-action-item].active');
            }

            return Array.from(items);
        }

        /**
         * Get all available items from given list
         *
         * @param list The action list
         *
         * @return array
         */
        getAllItems(list)
        {
            let items;
            if (list.tagName.toLowerCase() === 'table') {
                items = list.querySelectorAll(':scope > tbody > [data-action-item]');
            } else {
                items = list.querySelectorAll(':scope > [data-action-item]');
            }

            return Array.from(items);
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

            let url = anchor.getAttribute('href');
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
                return items[0].closest('.action-list').getAttribute('data-icinga-multiselect-url') + url;
            }

            return url;
        }

        onColumnClose(event) {
            let _this = event.data.self;
            let list = _this.findDetailUrlActionList(document.getElementById('col1'));
            if (list && list.matches('[data-icinga-multiselect-url], [data-icinga-detail-url]')) {
                _this.clearSelection(_this.getActiveItems(list));
                _this.addSelectionCountToFooter(list);
            }
        }

        /**
         * Find the action list using the detail url
         *
         * @param {Element} container
         *
         * @return Element|null
         */
        findDetailUrlActionList(container) {
            let detailUrl = this.icinga.utils.parseUrl(
                this.icinga.history.getCol2State().replace(/^#!/, '')
            );

            let detailItem = container.querySelector(
                '[data-icinga-detail-filter="'
                + detailUrl.query.replace('?', '') + '"],' +
                '[data-icinga-multiselect-filter="'
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
        onColumnMoved(event, sourceId) {
            let _this = event.data.self;

            if (event.target.id === 'col2' && sourceId === 'col1') { // only for browser-back (col1 shifted to col2)
                _this.clearSelection(event.target.querySelectorAll('.action-list .active'));
            } else if (event.target.id === 'col1' && sourceId === 'col2') {
                for (const requestNo of Object.keys(_this.activeRequests)) {
                    if (_this.activeRequests[requestNo] === sourceId) {
                        _this.enableAutoRefresh(_this.activeRequests[requestNo]);
                        _this.activeRequests[requestNo] = _this.suspendAutoRefresh(event.target);
                    }
                }
            }
        }

        onRendered(event, isAutoRefresh) {
            let _this = event.data.self;
            let container = event.target;
            let isTopLevelContainer = container.matches('#main > :scope');

            let list;
            if (event.currentTarget !== container || Object.keys(_this.activeRequests).length) {
                // Nested containers are not processed multiple times || still processing selection/navigation request
                return;
            } else if (isTopLevelContainer && container.id !== 'col1') {
                if (isAutoRefresh) {
                    return;
                }

                // only for browser back/forward navigation
                list = _this.findDetailUrlActionList(document.getElementById('col1'));
            } else {
                list = _this.findDetailUrlActionList(container);
            }

            if (! list || ! ("isDisplayContents" in list.dataset)) {
                // no detail view || ignore when already set
                let actionLists = null;
                if (! list) {
                    actionLists = document.querySelectorAll('.action-list');
                } else {
                    actionLists = [list];
                }

                for (let actionList of actionLists) {
                    let firstSelectableItem = actionList.querySelector(':scope > [data-action-item]');
                    if (
                        firstSelectableItem
                        && (
                            ! firstSelectableItem.checkVisibility()
                            && firstSelectableItem.firstChild
                            && firstSelectableItem.firstChild.checkVisibility()
                        )
                    ) {
                        actionList.dataset.isDisplayContents = "";
                    }
                }
            }

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

                let dashboard = list.closest('.dashboard');
                if (dashboard) {
                    _this.clearDashboardSelections(dashboard, list);
                }

                _this.clearSelection(_this.getAllItems(list).filter(item => !toActiveItems.includes(item)));
                _this.setActive(toActiveItems);
            }

            if (isTopLevelContainer) {
                let footerList = list ?? container.querySelector('.content > .action-list');
                if (footerList) {
                    _this.addSelectionCountToFooter(footerList);
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
