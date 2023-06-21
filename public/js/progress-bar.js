(function (Icinga) {

    "use strict";

    class ProgressBar extends Icinga.EventListener {
        constructor(icinga)
        {
            super(icinga);

            /**
             * Frame update threshold. If it reaches zero, the view is updated
             *
             * Currently, only every third frame is updated.
             *
             * @type {number}
             */
            this.frameUpdateThreshold = 3;

            /**
             * Threshold at which animations get smoothed out (in milliseconds)
             *
             * @type {number}
             */
            this.smoothUpdateThreshold = 250;

            this.on('rendered', '#main > .container', this.onRendered, this);
        }

        onRendered(event)
        {
            const _this = event.data.self;
            const container = event.target;

            container.querySelectorAll('[data-animate-progress]').forEach(progress => {
                const frequency = (
                    (Number(progress.dataset.endTime) - Number(progress.dataset.startTime)
                ) * 1000) / progress.parentElement.offsetWidth;

                _this.updateProgress(
                    now => _this.animateProgress(progress, now), frequency);
            });
        }

        animateProgress(progress, now)
        {
            if (! progress.isConnected) {
                return false; // Exit early if the node is removed from the DOM
            }

            const durationScale = 100;

            const startTime = Number(progress.dataset.startTime);
            const endTime = Number(progress.dataset.endTime);
            const duration = endTime - startTime;
            const end = new Date(endTime * 1000.0);

            let leftNow = durationScale * (1 - (end - now) / (duration * 1000.0));
            if (leftNow > durationScale) {
                leftNow = durationScale;
            } else if (leftNow < 0) {
                leftNow = 0;
            }

            const switchAfter = Number(progress.dataset.switchAfter);
            if (! isNaN(switchAfter)) {
                const switchClass = progress.dataset.switchClass;
                const switchAt = new Date((startTime * 1000.0) + (switchAfter * 1000.0));
                if (now < switchAt) {
                    progress.classList.add(switchClass);
                } else if (progress.classList.contains(switchClass)) {
                    progress.classList.remove(switchClass);
                }
            }

            const bar = progress.querySelector(':scope > .bar');
            bar.style.width = leftNow + '%';

            return leftNow !== durationScale;
        }

        updateProgress(callback, frequency, now = null)
        {
            if (now === null) {
                now = new Date();
            }

            if (! callback(now)) {
                return;
            }

            if (frequency < this.smoothUpdateThreshold) {
                let counter = this.frameUpdateThreshold;
                const onNextFrame = timeSinceOrigin => {
                    if (--counter === 0) {
                        this.updateProgress(callback, frequency, new Date(performance.timeOrigin + timeSinceOrigin));
                    } else {
                        requestAnimationFrame(onNextFrame);
                    }
                };
                requestAnimationFrame(onNextFrame);
            } else {
                setTimeout(() => this.updateProgress(callback, frequency), frequency);
            }
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.ProgressBar = ProgressBar;
})(Icinga);
