<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\ProvidedHook\Reporting;

use Icinga\Application\Icinga;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Reporting\ReportData;
use Icinga\Module\Reporting\ReportRow;
use Icinga\Module\Reporting\Timerange;
use ipl\Sql\Expression;
use ipl\Stdlib\Filter\Rule;

use function ipl\I18n\t;

class HostSlaReport extends SlaReport
{
    public function getName()
    {
        $name = t('Host SLA Report');
        if (Icinga::app()->getModuleManager()->hasEnabled('idoreports')) {
            $name .= ' (Icinga DB)';
        }

        return $name;
    }

    protected function createReportData()
    {
        return (new ReportData())
            ->setDimensions([t('Hostname')])
            ->setValues([t('SLA in %')]);
    }

    protected function createReportRow($row)
    {
        if ($row->sla === null) {
            return null;
        }

        return (new ReportRow())
            ->setDimensions([$row->display_name])
            ->setValues([(float) $row->sla]);
    }

    protected function fetchSla(Timerange $timerange, Rule $filter = null)
    {
        $sla = Host::on($this->getDb())
            ->columns([
                'display_name',
                'sla' => new Expression(sprintf(
                    "get_sla_ok_percent(%s, NULL, '%s', '%s')",
                    'host.id',
                    $timerange->getStart()->format('Uv'),
                    $timerange->getEnd()->format('Uv')
                ))
            ]);

        $this->applyRestrictions($sla);

        if ($filter !== null) {
            $sla->filter($filter);
        }

        return $sla;
    }
}
