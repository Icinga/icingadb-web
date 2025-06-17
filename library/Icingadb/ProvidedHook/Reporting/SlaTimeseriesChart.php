<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\ProvidedHook\Reporting;

use Icinga\Util\Json;
use ipl\Html\BaseHtmlElement;
use ipl\Html\ValidHtml;
use ipl\I18n\StaticTranslator;

class SlaTimeseriesChart extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = [
        'class'                             => ['icinga-chart'],
        'data-chart-show-legend'            => false,
        'data-chart-x-axis-type'            => 'timeseries',
        'data-chart-y-axis-min'             => 0,
        'data-chart-y-axis-max'             => 100,
        'data-chart-below-threshold-color'  => '#ff5566',
        'data-chart-above-threshold-color'  => '#44bb77',
        'data-chart-line-color'             => 'rgba(85,85,85,0.5)'
    ];

    /** @var string The chart type (line, bar) */
    protected $type;

    /** @var ValidHtml The title */
    protected $title;

    /** @var array The chart data ['dataPoints' => [...], 'xAxisTicks' => [...]] */
    protected $data;

    /** @var string The locale to format timeseries */
    protected $locale;

    /** @var string The type of the x-axis ticks (hour, week, month, year) */
    protected $xAxisTicksType = 'hour';

    /** @var float|int The threshold value for the chart */
    protected $threshold = SlaChartReport::DEFAULT_THRESHOLD;

    /**
     * @param string $type The type of the chart (line, bar)
     * @param ValidHtml $title The title of the chart
     * @param array $data The data for the chart
     *
     */
    public function __construct(string $type, ValidHtml $title, array $data = [])
    {
        $this->type = $type;
        $this->title = $title;
        $this->data = $data;

        if (method_exists(StaticTranslator::$instance, 'getLocale')) {
            $this->locale = str_replace('_', '-', StaticTranslator::$instance->getLocale());
        }
    }

    /**
     * Set the type of the x-axis ticks (hour, week, month, year)
     *
     * @param string $xAxisTicksType
     *
     * @return $this
     */
    public function setXAxisTicksType(string $xAxisTicksType): self
    {
        $this->xAxisTicksType = $xAxisTicksType;

        return $this;
    }

    /**
     * Set the threshold value for the chart
     *
     * @param float|int $threshold
     *
     * @return $this
     */
    public function setThreshold(float $threshold): self
    {
        $this->threshold = $threshold;

        return $this;
    }

    protected function assemble(): void
    {
        $this->addAttributes([
            'data-chart-type'               => $this->type,
            'data-chart-data'               => Json::encode($this->data),
            'data-chart-x-axis-label'       => t('Time'),
            'data-chart-y-axis-label'       => t('Percent'),
            'data-chart-time-format-locale' => $this->locale,
            'data-chart-x-axis-ticks-type'  => $this->xAxisTicksType,
            'data-chart-threshold'          => $this->threshold
        ]);

        $this->addHtml($this->title);
    }
}