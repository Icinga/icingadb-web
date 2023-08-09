<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Hook\Common;

use Icinga\Module\Icingadb\ProvidedHook\Reporting\HostSlaReport;
use Icinga\Module\Reporting\Timerange;
use ipl\Html\Html;
use ipl\Web\Widget\EmptyState;

use function ipl\I18n\t;

trait TotalSlaReportUtils
{
    public function getHtml(Timerange $timerange, array $config = null)
    {
        $data = $this->getData($timerange, $config);
        $count = $data->count();

        if (! $count) {
            return new EmptyState(t('No data found.'));
        }

        $threshold =  (float) ($config['threshold'] ?? static::DEFAULT_THRESHOLD);

        $tableRows = [];
        $precision = $config['sla_precision'] ?? static::DEFAULT_REPORT_PRECISION;

        // We only have one average
        $average = $data->getAverages()[0];

        if ($average < $threshold) {
            $slaClass = 'nok';
        } else {
            $slaClass = 'ok';
        }

        $total = $this instanceof HostSlaReport
            ? sprintf(t('Total (%d Hosts)'), $count)
            : sprintf(t('Total (%d Services)'), $count);

        $tableRows[] = Html::tag('tr', null, [
            Html::tag('td', ['colspan' => count($data->getDimensions())], $total),
            Html::tag('td', ['class' => "sla-column $slaClass"], round($average, $precision))
        ]);

        $table = Html::tag(
            'table',
            ['class' => 'common-table sla-table'],
            [Html::tag('tbody', null, $tableRows)]
        );

        return $table;
    }
}
