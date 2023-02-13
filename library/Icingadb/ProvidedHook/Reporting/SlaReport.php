<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\ProvidedHook\Reporting;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\ProvidedHook\Reporting\Common\ReportData;
use Icinga\Module\Icingadb\ProvidedHook\Reporting\Common\SlaReportUtils;
use Icinga\Module\Icingadb\Widget\EmptyState;
use Icinga\Module\Reporting\Hook\ReportHook;
use Icinga\Module\Reporting\ReportRow;
use Icinga\Module\Reporting\Timerange;
use ipl\Html\Form;
use ipl\Html\Html;

use function ipl\I18n\t;

/**
 * Base class for host and service SLA reports
 */
abstract class SlaReport extends ReportHook
{
    use Auth;
    use Database;
    use SlaReportUtils;

    /** @var float If an SLA value is lower than the threshold, it is considered not ok */
    protected const DEFAULT_THRESHOLD = 99.5;

    /** @var int The amount of decimal places for the report result */
    protected const DEFAULT_REPORT_PRECISION = 2;

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
        return $this->fetchReportData($timerange->getStart(), $timerange->getEnd(), $config);
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

            if ($sla === null) {
                $slaClass = 'unknown';
            } elseif ($sla < $threshold) {
                $slaClass = 'nok';
            } else {
                $slaClass = 'ok';
            }

            $cells[] = Html::tag(
                'td',
                ['class' => "sla-column $slaClass"],
                $sla === null ? t('N/A') : round($sla, $precision)
            );

            $tableRows[] = Html::tag('tr', null, $cells);
        }

        // We only have one average
        $average = $data->getAverages()[0];

        if ($average === null) {
            $slaClass = 'unknown';
        } elseif ($average < $threshold) {
            $slaClass = 'nok';
        } else {
            $slaClass = 'ok';
        }

        $total = $this instanceof HostSlaReport
            ? sprintf(t('Total (%d Hosts)'), $data->count())
            : sprintf(t('Total (%d Services)'), $data->count());

        $tableRows[] = Html::tag('tr', null, [
            Html::tag('td', ['colspan' => count($data->getDimensions())], $total),
            Html::tag(
                'td',
                ['class' => "sla-column $slaClass"],
                $average === null ? t('N/A') : round($average, $precision)
            )
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

        // echo '<pre>' .  nl2br($data->getTimelineString()) . '</pre>';
        return $table;
    }
}
