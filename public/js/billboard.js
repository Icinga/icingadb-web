;(function (Icinga) {

    "use strict";

    try {
        var bb = require('icinga/icinga-php-library/vendor/billboard');
    } catch (e) {
        console.warn('Unable to provide billboard feature. Libraries not available:', e);
        return;
    }

    const FALLBACK_COLOR = 'green';

    /**
     * ## Supported data attributes:
     *
     * #### Required:
     * - chartData {json}: Chart data (dataPoints: [int|float], xAxisTicks: [int|float|string])
     *
     * #### Optional:
     * - chartType {string}: Chart type [line, bar, area...] (default: line)
     * - chartXAxisType {string}: X-axis type [timeseries, category, indexed, log] (default: indexed)
     * - chartXAxisTicksType {string}: X-axis ticks type (hour, day, week, month) [only for chartXAxisType: timeseries] (default: detected automatically)
     * - chartTimeFormatLocale {string}: Locale for the time format [only for chartXAxisType: timeseries] (default: 'en-US')
     * - chartYAxisLabel {string}: Label for the y-axis (default: null)
     * - chartXAxisLabel {string}: Label for the x-axis (default: null)
     * - chartShowLegend {boolean}: Show legend (default: false)
     * - chartYAxisMax {int|float}: Max value for the y-axis (default: 100)
     * - chartYAxisMin {int|float}: Min value for the y-axis (default: 0)
     * - chartThreshold {int|float}: Threshold value for the chart (default: null)
     * - chartLineColor {string}: Color for the line (only for line chart) (default: FALLBACK_COLOR)
     * - chartBelowThresholdColor {string}: Color for values below the threshold (default: FALLBACK_COLOR)
     * - chartAboveThresholdColor {string}: Color for values above the threshold (default: FALLBACK_COLOR)
     * - chartTooltipLabel {string}: Datapoint label for the tooltip (default: '')
     */

    class BillboardBehavior extends Icinga.EventListener {
        constructor(icinga)
        {
            super(icinga);

            this.on('rendered', '#main > .container', this.onRendered, this);
        }

        onRendered(event)
        {
            let _this = event.data.self;

            event.currentTarget.querySelectorAll('.icinga-chart').forEach(element => {
                let attrs = element.dataset;
                let chartData = JSON.parse(attrs.chartData);

                let dataPoints = chartData.dataPoints;
                let xAxisTicks = chartData.xAxisTicks;

                let lineColor = attrs.chartLineColor ?? FALLBACK_COLOR;
                let threshold = attrs.chartThreshold;
                let belowThresholdColor = attrs.chartBelowThresholdColor ?? FALLBACK_COLOR;
                let aboveThresholdColor = attrs.chartAboveThresholdColor ?? FALLBACK_COLOR;
                let yAxisMax = Number(attrs.chartYAxisMax ?? 100);
                let yAxisMin = Number(attrs.chartYAxisMin ?? 0);

                var grid = {};
                if (threshold) {
                   grid = {
                       y: {
                           lines: [
                               {
                                   value: threshold,
                                   text: "threshold",
                                   class: "threshold-mark",
                               },
                           ]
                       },
                   };
                }

                let chartElement = document.createElement('div');
                chartElement.classList.add('chart-element');

                element.appendChild(chartElement);

                bb.default.generate({
                    bindto: chartElement,
                    clipPath: false, // show line on 0 and 100
                    zoom: {
                        enabled: true,
                        type: "drag"
                    },
                    data: {
                        labels: {
                            rotate: 90,
                            format: (v, id, i, texts)=> {
                                return (v === yAxisMin || v === yAxisMax) ? '' : v;
                            },
                        },
                        type: attrs.chartType,
                        x: "xAxisTicks",
                        columns: [
                            ["xAxisTicks"].concat(xAxisTicks),
                            [attrs.chartTooltipLabel ?? ''].concat(dataPoints)
                        ],
                        color: (color, datapoint) => {
                            if (! ("value" in datapoint)) {
                                return lineColor;
                            }

                            return datapoint.value <= threshold ? belowThresholdColor : aboveThresholdColor;
                        },
                    },
                    axis: {
                        y: {
                            max: yAxisMax,
                            min: yAxisMin,
                            padding: {
                                top: 0,
                                bottom: 0
                            },
                            label: {
                                text: attrs.chartYAxisLabel,
                                position: "outer-middle",
                            },
                        },
                        x: {
                            type: attrs.chartXAxisType,
                            label: {
                                text: attrs.chartXAxisLabel,
                                position: "outer-center",
                            },
                            tick: {
                                multiline: true,
                                rotate: 60,
                                culling: {
                                    max: (xAxisTicks.length / 2) < 50 ? xAxisTicks.length + 1 : (xAxisTicks.length / 2)
                                },
                                format: _this.getFormaterFunction(attrs, xAxisTicks),
                            },
                            clipPath: false,
                            padding: {
                                right: 30,
                                unit: "px"
                            }
                        }
                    },
                    legend: {
                        show: 'chartShowLegend' in attrs
                    },
                    grid : grid,
                });
            });
        }

        /**
         * Get the formatter function based on the chart's x-axis type
         * @param attrs
         * @param xAxisTicks
         * @return {null}
         */
        getFormaterFunction(attrs, xAxisTicks) {
            let formatterFunc = null;
            switch (attrs.chartXAxisType) {
                case 'timeseries':
                    formatterFunc = (dateObj) => {
                        let options = {timeZone: this.icinga.config.timezone};
                        switch (attrs.chartXAxisTicksType) {
                            case 'hour':
                                options.hour = 'numeric';
                                options.minute = 'numeric';

                                if (xAxisTicks.length > 24) {
                                    options.year = '2-digit';
                                    options.month = 'short';
                                    options.day = 'numeric';
                                }

                                break;
                            case 'day':
                            case 'week':
                                options.year = '2-digit';
                                options.month = 'short';
                                options.day = 'numeric';
                                break;
                            case 'month':
                                options.year = '2-digit';
                                options.month = 'short';
                                break;
                        }

                        let locale = attrs.chartTimeFormatLocale ?? 'en-US';

                        let localeFormatter = Intl.DateTimeFormat(locale, options);

                        if (attrs.chartXAxisTicksType === 'week') {
                            var current = dateObj.getTime();
                            var next = xAxisTicks[xAxisTicks.indexOf(current) + 1];

                            return localeFormatter.formatRange(current, next ? next - 1 : current);
                        }

                        return localeFormatter.format(dateObj);
                    };
                    break;
                case 'category':
                    formatterFunc = (index, categoryName) => {
                        return categoryName;
                    };
                    break;
                    case "indexed":
                    case "log":
                        formatterFunc = (logOrIndex) => {
                            return logOrIndex;
                        };
            }

            return formatterFunc;
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.BillboardBehavior = BillboardBehavior;
})(Icinga);
