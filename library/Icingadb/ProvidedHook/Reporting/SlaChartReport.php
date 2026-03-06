<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\ProvidedHook\Reporting;

use DateInterval;
use DatePeriod;
use DateTime;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Reporting\Hook\ReportHook;
use Icinga\Module\Reporting\Timerange;
use ipl\Html\Attributes;
use ipl\Html\Form;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Stdlib\Filter\Rule;
use ipl\Web\Filter\QueryString;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\StateBall;

use function ipl\I18n\t;

/**
 * Base class for host and service SLA chart reports
 */
abstract class SlaChartReport extends ReportHook
{
    use Auth;
    use Database;

    /** @var float If an SLA value is lower than the threshold, it is considered not ok */
    const DEFAULT_THRESHOLD = 99.5;

    /** @var int The amount of decimal places for the report result */
    const DEFAULT_REPORT_PRECISION = 2;

    /**
     * Fetch SLA according to specified time range and filter
     *
     * @param Timerange $timerange
     * @param Rule|null $filter
     *
     * @return iterable
     */
    abstract protected function fetchSla(Timerange $timerange, Rule $filter = null);

    public function getData(Timerange $timerange, array $config = null)
    {
        return $this->fetchReportData($timerange, $config);
    }
    protected function fetchReportData(Timerange $timerange, array $config = null)
    {
        $filter = trim((string)$config['filter']) ?: '*';
        $filter = $filter !== '*' ? QueryString::parse($filter) : null;

        $interval = null;
        $boundary = null;

        switch ($config['breakdown']) {
            case 'hour':
                $interval = new DateInterval('PT1H');
                $boundary = '+1 hour';

                break;
            case 'day':
                $interval = new DateInterval('P1D');
                $boundary = 'tomorrow midnight';

                break;
            case 'week':
                $interval = new DateInterval('P1W');
                $boundary = 'monday next week midnight';

                break;
            case 'month':
                $interval = new DateInterval('P1M');
                $boundary = 'first day of next month midnight';

                break;
        }

        $precision = $config['sla_precision'] ?? static::DEFAULT_REPORT_PRECISION;
        $data = [];
        foreach ($this->yieldTimerange($timerange, $interval, $boundary) as [$start, $end]) {
            foreach ($this->fetchSla(new Timerange($start, $end), $filter) as $row) {
                if ($row->sla === null) {
                    continue;
                }

                if (! isset($data[$row->id]['title'])) {
                    if ($row instanceof Service) {
                        $title = sprintf(
                            t('%s on %s', '<service> on <host>'),
                            $row->display_name,
                            $row->host->display_name
                        );
                    } else {
                        $title = $row->display_name;
                    }

                    $data[$row->id]['title'] = $title;
                }

                $data[$row->id]['xAxisTicks'][] = (int) $start->format('Uv');
                $data[$row->id]['dataPoints'][] = round($row->sla, $precision);
            }
        }

        return $data;
    }

    /**
     * Yield start and end times that recur at the specified interval over the given time range
     *
     * @param Timerange $timerange
     * @param DateInterval $interval
     * @param string|null $boundary English text datetime description for calculating bounds to get
     *                               calendar days, weeks or months instead of relative times according to interval
     *
     * @return \Generator
     */
    protected function yieldTimerange(Timerange $timerange, DateInterval $interval, $boundary = null)
    {
        $start = clone $timerange->getStart();
        $end = clone $timerange->getEnd();
        $oneSecond = new DateInterval('PT1S');

        if ($boundary !== null) {
            $intermediate = (clone $start)->modify($boundary);
            if ($intermediate < $end) {
                yield [clone $start, $intermediate->sub($oneSecond)];

                $start->modify($boundary);
            }
        }

        $period = new DatePeriod($start, $interval, $end, DatePeriod::EXCLUDE_START_DATE);

        foreach ($period as $date) {
            /** @var \DateTime $date */
            yield [$start, (clone $date)->sub($oneSecond)];

            $start = $date;
        }

        yield [$start, $end];
    }

    public function initConfigForm(Form $form)
    {
        $form->addElement('text', 'filter', [
            'label' => t('Filter')
        ]);

        $form->addElement('select', 'chart_type', [
            'label' => t('Chart Type'),
            'options' => [
                null => t('Please choose'),
                'line' => t('Line'),
                'bar' => t('Bar')
            ],
            'disabledOptions' => [null],
            'required' => true
        ]);

        $breakdownOptions = [
            null    => t('Please choose'),
            'hour'  => t('Hour'),
            'day'   => t('Day'),
            'week'  => t('Week'),
            'month' => t('Month')
        ];

        $form->addElement('select', 'breakdown', [
            'label'             => t('Breakdown'),
            'options'           => $breakdownOptions,
            'disabledOptions'   => [null],
            'required'          => true,
        ]);

        $timeframe = $form->getPopulatedValue('timeframe_instance');
        if ($timeframe) {
            $diffDays = (new DateTime($timeframe->start))
                ->diff(new DateTime($timeframe->end))
                ->days;

            $toDisable = [];
            // maximum 150 data points should be visible, disable breakdowns that would exceed this
            if ($diffDays > 5) {
                $toDisable[] = 'hour';

                if ($diffDays > 150) {
                    $toDisable[] = 'day';
                }

                if ($diffDays / 7 > 150) {
                    $toDisable[] = 'week';
                }

                if ($diffDays / 30 > 150) {
                    $toDisable[] = 'month';
                }

                $disableDescription = t('The selected timeframe is too large for this breakdown');
                foreach ($toDisable as $key) {
                    $breakdownOptions[$key] = sprintf('%s (%s)', $breakdownOptions[$key], $disableDescription);
                }

                $form->getElement('breakdown')
                   ->addAttributes([
                       'options'            => $breakdownOptions,
                       'disabledOptions'    => array_merge([null], $toDisable)
                   ]);
            }
        }

        $form->addElement('number', 'threshold', [
            'label' => t('Threshold'),
            'placeholder' => static::DEFAULT_THRESHOLD,
            'step' => '0.01',
            'min' => '1',
            'max' => '100'
        ]);

        $form->addElement('number', 'sla_precision', [ // TODO: required?
            'label' => t('Amount Decimal Places'),
            'placeholder' => static::DEFAULT_REPORT_PRECISION,
            'min' => '1',
            'max' => '12'
        ]);
    }

    public function getHtml(Timerange $timerange, array $config = null)
    {
        $data = $this->fetchReportData($timerange, $config);

        if (! count($data)) {
            return new EmptyState(t('No data found.'));
        }

        $threshold = isset($config['threshold']) ? (float)$config['threshold'] : static::DEFAULT_THRESHOLD;
        $charts = [];
        foreach ($data as $chartData) {
            $title = new HtmlElement(
                'div',
                Attributes::create(['class' => 'icinga-chart-title']),
                HtmlString::create($chartData['title'])
            );
            unset($chartData['title']);
            $charts[] = new HtmlElement(
                'div',
                Attributes::create(['class' => 'sla-chart-wrapper']),
                (new SlaTimeseriesChart($config['chart_type'], $title, $chartData))
                    ->setXAxisTicksType($config['breakdown'])
                    ->setThreshold($threshold)
            );
        }

        $table = new HtmlElement('table', Attributes::create(['class' => ['sla-chart-table']]));

        $table->addHtml(
            new HtmlElement(
                'thead',
                null,
                new HtmlElement(
                    'tr',
                    null,
                    new HtmlElement(
                        'th',
                        Attributes::create(['class' => 'legend']),
                        new HtmlElement(
                            'span',
                            Attributes::create(['class' => 'above-threshold']),
                            new StateBall('ok', StateBall::SIZE_MEDIUM_LARGE),
                            HtmlString::create('SLA in > ' . $threshold)
                        ),
                        new HtmlElement(
                            'span',
                            Attributes::create(['class' => 'below-threshold']),
                            new StateBall('down', StateBall::SIZE_MEDIUM_LARGE),
                            HtmlString::create('SLA in <= ' . $threshold)
                        )
                    )
                )
            )
        );

        $table->addHtml(
            new HtmlElement(
                'tbody',
                null,
                new HtmlElement(
                    'tr',
                    null,
                    new HtmlElement(
                        'td',
                        Attributes::create(['data-pdfexport-page-breaks-at' => '.sla-chart-wrapper']),
                        ...$charts
                    )
                )
            )
        );

        return $table;
    }
}
