;(function () {

    "use strict";

    Icinga.Behaviors = Icinga.Behaviors || {};

    var ActionList = function (icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('click', '.action-list > .list-item', this.onClick, this);
    };

    ActionList.prototype = new Icinga.EventListener();

    ActionList.prototype.onClick = function (event) {
        var _this = event.data.self;
        var $activeItems;
        var $item = $(this);
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

    Icinga.Behaviors.ActionList = ActionList;
}());
