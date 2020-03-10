;(function () {

    "use strict";

    /**
     * Remove one leading and trailing bracket and all text outside those brackets
     *
     * @param {string} subject
     *
     * @returns {string}
     */
    var stripBrackets = function (subject) {
        return subject.replace(/^[^(]*\({1,2}/, '(').replace(/\){1,2}[^)]*$/, ')');
    };

    /**
     * Parse the filter query contained in the given URL query string
     *
     * @param {string} queryString
     *
     * @returns {array}
     */
    var parseSelectionQuery = function (queryString) {
        return stripBrackets(queryString).split('|');
    };

    Icinga.Behaviors = Icinga.Behaviors || {};

    var ActionList = function (icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('click', '.action-list > .list-item, .action-list > .list-item a', this.onClick, this);
        this.on('close-column', this.onColumnClose, this);

        this.on('rendered', '.container', this.onRendered, this);
    };

    ActionList.prototype = new Icinga.EventListener();

    ActionList.prototype.onClick = function (event) {
        var _this = event.data.self;
        var $activeItems;
        var $item = $(this).closest('.list-item');
        var $list = $item.parent('.action-list');

        event.preventDefault();
        event.stopPropagation();

        if ($list.is('[data-icinga-multiselect-url]')) {
            if (event.ctrlKey || event.metaKey) {
                $item.toggleClass('active');
            } else if (event.shiftKey) {
                document.getSelection().removeAllRanges();

                $activeItems = $list.find('.list-item.active');

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
                $list.find('.list-item.active').removeClass('active');
                $item.addClass('active');
            }
        } else {
            $list.find('.list-item.active').removeClass('active');
            $item.addClass('active');
        }

        $activeItems = $list.find('.list-item.active');

        if ($activeItems.length === 0) {
            if (_this.icinga.loader.getLinkTargetFor($item).attr('id') === 'col2') {
                _this.icinga.ui.layout1col();
            }
        } else {
            var url;

            if ($activeItems.length === 1) {
                url = $item.find('[href]').first().attr('href');
            } else {
                var filters = $activeItems.map(function () {
                    return $(this).attr('data-icinga-multiselect-filter');
                });

                url = $list.attr('data-icinga-multiselect-url') + '?(' + filters.toArray().join('|') + ')';
            }

            _this.icinga.loader.loadUrl(
                url, _this.icinga.loader.getLinkTargetFor($item)
            );
        }
    };

    ActionList.prototype.onColumnClose = function (event) {
        var $target = $(event.target);

        if ($target.attr('id') !== 'col2') {
            return;
        }

        var $list = $('#col1').find('.action-list');
        if ($list.length && $list.is('[data-icinga-multiselect-url]')) {
            var _this = event.data.self;
            var detailUrl = _this.icinga.utils.parseUrl(_this.icinga.history.getCol2State().replace(/^#!/, ''));

            if ($list.attr('data-icinga-multiselect-url') === detailUrl.path) {
                $.each(parseSelectionQuery(detailUrl.query), function (i, filter) {
                    $list.find(
                        '[data-icinga-multiselect-filter="' + filter + '"]'
                    ).removeClass('active');
                });
            } else if ($list.attr('data-icinga-detail-url') === detailUrl.path) {
                $list.find(
                    '[data-icinga-detail-filter="' + detailUrl.query.slice(1) + '"]'
                ).removeClass('active');
            }
        }
    };

    ActionList.prototype.onRendered = function (event) {
        var $target = $(event.target);

        if ($target.attr('id') !== 'col1') {
            return;
        }

        var $list = $target.find('.action-list');

        if ($list.length && $list.is('[data-icinga-multiselect-url]')) {
            var _this = event.data.self;
            var detailUrl = _this.icinga.utils.parseUrl(_this.icinga.history.getCol2State().replace(/^#!/, ''));

            if ($list.attr('data-icinga-multiselect-url') === detailUrl.path) {
                $.each(parseSelectionQuery(detailUrl.query), function (i, filter) {
                    $list.find(
                        '[data-icinga-multiselect-filter="' + filter + '"]'
                    ).addClass('active');
                });
            } else if ($list.attr('data-icinga-detail-url') === detailUrl.path) {
                $list.find(
                    '[data-icinga-detail-filter="' + detailUrl.query.slice(1) + '"]'
                ).addClass('active');
            }
        }
    };

    Icinga.Behaviors.ActionList = ActionList;
}());
