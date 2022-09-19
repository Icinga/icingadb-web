<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\ProvidedHook\Reporting;

use DateInterval;
use DatePeriod;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Widget\EmptyState;
use Icinga\Module\Reporting\Hook\ReportHook;
use Icinga\Module\Reporting\ReportData;
use Icinga\Module\Reporting\ReportRow;
use Icinga\Module\Reporting\Timerange;
use ipl\Html\Form;
use ipl\Html\Html;
use ipl\Stdlib\Filter\Rule;
use ipl\Web\Filter\QueryString;

use function ipl\I18n\t;

/**
 * Base class for host and service SLA reports
 */
abstract class SlaReport extends ReportHook
{
    use Auth;
    use Database;

    /** @var float If an SLA value is lower than the threshold, it is considered not ok */
    public const DEFAULT_THRESHOLD = 99.5;

    /** @var int The amount of decimal places for the report result */
    public const DEFAULT_REPORT_PRECISION = 2;

    /**
     * Create and return a {@link ReportData} container
     *
     * @return ReportData Container initialized with the expected dimensions and value labels for the specific report
     */
    abstract protected function createReportData();

    /**
     * Create and return a {@link ReportRow}
     *
     * @param mixed $row Data for the row
     *
     * @return ReportRow|null Row with the dimensions and values for the specific report set according to the data
     *                        expected in {@link createRepportData()} or null for no data
     */
    abstract protected function createReportRow($row);

    /**
     * Fetch SLA according to specified time range and filter
     *
     * @param Timerange $timerange
     * @param Rule|null $filter
     *
     * @return iterable
     */
    abstract protected function fetchSla(Timerange $timerange, Rule $filter = null);

    protected function fetchReportData(Timerange $timerange, array $config = null)
    {
        $rd = $this->createReportData();
        $rows = [];

        $filter = trim((string) $config['filter']) ?: '*';
        $filter = $filter !== '*' ? QueryString::parse($filter) : null;

        if (isset($config['breakdown']) && $config['breakdown'] !== 'none') {
            switch ($config['breakdown']) {
                case 'day':
                    $interval = new DateInterval('P1D');
                    $format = 'Y-m-d';
                    $boundary = 'tomorrow midnight';

                    break;
                case 'week':
                    $interval = new DateInterval('P1W');
                    $format = 'Y-\WW';
                    $boundary = 'monday next week midnight';

                    break;
                case 'month':
                    $interval = new DateInterval('P1M');
                    $format = 'Y-m';
                    $boundary = 'first day of next month midnight';

                    break;
            }

            $dimensions = $rd->getDimensions();
            $dimensions[] = ucfirst($config['breakdown']);
            $rd->setDimensions($dimensions);

            foreach ($this->yieldTimerange($timerange, $interval, $boundary) as list($start, $end)) {
                foreach ($this->fetchSla(new Timerange($start, $end), $filter) as $row) {
                    $row = $this->createReportRow($row);

                    if ($row === null) {
                        continue;
                    }

                    $dimensions = $row->getDimensions();
                    $dimensions[] = $start->format($format);
                    $row->setDimensions($dimensions);

                    $rows[] = $row;
                }
            }
        } else {
            foreach ($this->fetchSla($timerange, $filter) as $row) {
                $rows[] = $this->createReportRow($row);
            }
        }

        $rd->setRows($rows);

        return $rd;
    }

    /**
     * Yield start and end times that recur at the specified interval over the given time range
     *
     * @param Timerange    $timerange
     * @param DateInterval $interval
     * @param string|null  $boundary English text datetime description for calculating bounds to get
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

        $form->addElement('select', 'breakdown', [
            'label'   => t('Breakdown'),
            'options' => [
                'none'  => t('None', 'SLA Report Breakdown'),
                'day'   => t('Day'),
                'week'  => t('Week'),
                'month' => t('Month')
            ]
        ]);

        $form->addElement('number', 'threshold', [
            'label'       => t('Threshold'),
            'placeholder' => static::DEFAULT_THRESHOLD,
            'step'        => '0.01',
            'min'         => '1',
            'max'         => '100'
        ]);

        $form->addElement('number', 'sla_precision', [
            'label'       => t('Amount Decimal Places'),
            'placeholder' => static::DEFAULT_REPORT_PRECISION,
            'min'         => '1',
            'max'         => '12'
        ]);
    }

    public function getData(Timerange $timerange, array $config = null)
    {
        return $this->fetchReportData($timerange, $config);
    }

    public function getHtml(Timerange $timerange, array $config = null)
    {
        $data = $this->getData($timerange, $config);

        if (! count($data)) {
            return new EmptyState(t('No data found.'));
        }

        $threshold = isset($config['threshold']) ? (float) $config['threshold'] : static::DEFAULT_THRESHOLD;

        $tableHeaderCells = [];

        foreach ($data->getDimensions() as $dimension) {
            $tableHeaderCells[] = Html::tag('th', null, $dimension);
        }

        foreach ($data->getValues() as $value) {
            $tableHeaderCells[] = Html::tag('th', null, $value);
        }

        $tableRows = [];
        $precision = $config['sla_precision'] ?? static::DEFAULT_REPORT_PRECISION;

        foreach ($data->getRows() as $row) {
            $cells = [];

            foreach ($row->getDimensions() as $dimension) {
                $cells[] = Html::tag('td', null, $dimension);
            }

            // We only have one metric
            $sla = $row->getValues()[0];

            if ($sla < $threshold) {
                $slaClass = 'nok';
            } else {
                $slaClass = 'ok';
            }

            $cells[] = Html::tag('td', ['class' => "sla-column $slaClass"], round($sla, $precision));

            $tableRows[] = Html::tag('tr', null, $cells);
        }

        // We only have one average
        $average = $data->getAverages()[0];

        if ($average < $threshold) {
            $slaClass = 'nok';
        } else {
            $slaClass = 'ok';
        }

        $total = $this instanceof HostSlaReport
            ? sprintf(t('Total (%d Hosts)'), $data->count())
            : sprintf(t('Total (%d Services)'), $data->count());

        $tableRows[] = Html::tag('tr', null, [
            Html::tag('td', ['colspan' => count($data->getDimensions())], $total),
            Html::tag('td', ['class' => "sla-column $slaClass"], round($average, $precision))
        ]);

        $table = Html::tag(
            'table',
            ['class' => 'common-table sla-table'],
            [
                Html::tag(
                    'thead',
                    null,
                    Html::tag(
                        'tr',
                        null,
                        $tableHeaderCells
                    )
                ),
                Html::tag('tbody', null, $tableRows)
            ]
        );

        return $table;
    }
}
