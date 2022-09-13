/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

;(function(Icinga, $) {

    'use strict';

    Icinga.Behaviors = Icinga.Behaviors || {};

    class LoadMore extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            this.on('click', '.show-more[data-no-icinga-ajax] a', this.onClick, this);
            this.on('keypress', '.show-more[data-no-icinga-ajax] a', this.onKeyPress, this);
        }

        onClick(event) {
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
                // Add showCompact, we don't want controls in paged results
                _this.icinga.utils.addUrlFlag(url, 'showCompact'),
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
        }

        onKeyPress(event) {
            if (event.which === 32) {
                event.data.self.onClick(event);
            }
        }
    }

    Icinga.Behaviors.LoadMore = LoadMore;

})(Icinga, jQuery);
