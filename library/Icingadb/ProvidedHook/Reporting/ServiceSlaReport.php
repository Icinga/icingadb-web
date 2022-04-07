<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\ProvidedHook\Reporting;

use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Reporting\ReportData;
use Icinga\Module\Reporting\ReportRow;
use Icinga\Module\Reporting\Timerange;
use ipl\Sql\Expression;
use ipl\Stdlib\Filter\Rule;

use function ipl\I18n\t;

class ServiceSlaReport extends SlaReport
{
    public function getName()
    {
        return t('Icinga DB Service SLA Report');
    }

    protected function createReportData()
    {
        return (new ReportData())
            ->setDimensions([t('Hostname'), t('Service Name')])
            ->setValues([t('SLA in %')]);
    }

    protected function createReportRow($row)
    {
        if ($row->sla === null) {
            return null;
        }

        return (new ReportRow())
            ->setDimensions([$row->host->display_name, $row->display_name])
            ->setValues([(float) $row->sla]);
    }

    protected function fetchSla(Timerange $timerange, Rule $filter = null)
    {
        $sla = Service::on($this->getDb())
            ->columns([
                'host.display_name',
                'display_name',
                'sla' => new Expression(sprintf(
                    "get_sla_ok_percent(%s, %s, '%s', '%s')",
                    'service.host_id',
                    'service.id',
                    $timerange->getStart()->format('Uv'),
                    $timerange->getEnd()->format('Uv')
                ))
            ]);

        $sla->resetOrderBy()->orderBy('host.display_name')->orderBy('display_name');

        $this->applyRestrictions($sla);

        if ($filter !== null) {
            $sla->filter($filter);
        }

        return $sla;
    }
}
