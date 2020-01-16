;(function(Icinga, $) {

    'use strict';

    Icinga.Behaviors = Icinga.Behaviors || {};

    /**
     * Icinga DB Load More behavior.
     *
     * @param icinga {Icinga} The current Icinga Object
     */
    var LoadMore = function(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.icinga = icinga;

        this.on('click', '.show-more[data-no-icinga-ajax] a', this.onClick, this);
        this.on('keypress', '.show-more[data-no-icinga-ajax] a', this.onKeyPress, this);
    };

    LoadMore.prototype = new Icinga.EventListener();

    LoadMore.prototype.onClick = function(event) {
        var _this = event.data.self;
        var $anchor = $(event.target);
        var $showMore = $anchor.parent();

        event.stopPropagation();
        event.preventDefault();

        var progressTimer = _this.icinga.timer.register(function () {
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
        var req = _this.icinga.loader.loadUrl(
            // Add view=compact, we don't want controls in paged results
            _this.icinga.utils.addUrlParams(url, { view: 'compact' }),
            $showMore.parent(),
            undefined,
            undefined,
            'append',
            false,
            progressTimer
        );
        req.addToHistory = false;
        req.done(function () {
            $showMore.remove();

            // Set data-icinga-url to make it available for Icinga.History.getCurrentState()
            req.$target.closest('.container').data('icingaUrl', url);

            _this.icinga.history.replaceCurrentState();
        });

        return false;
    };

    LoadMore.prototype.onKeyPress = function(event) {
        if (event.which === 32) {
            event.data.self.onClick(event);
        }
    };

    Icinga.Behaviors.LoadMore = LoadMore;

})(Icinga, jQuery);
