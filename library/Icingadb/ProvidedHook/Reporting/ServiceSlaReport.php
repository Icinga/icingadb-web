<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\ProvidedHook\Reporting;

use Icinga\Application\Icinga;
use Icinga\Module\Icingadb\ProvidedHook\Reporting\Common\ReportData;
use Icinga\Module\Reporting\ReportRow;

use function ipl\I18n\t;

class ServiceSlaReport extends SlaReport
{
    public function getName()
    {
        $name = t('Service SLA');
        if (Icinga::app()->getModuleManager()->hasEnabled('idoreports')) {
            $name .= ' (Icinga DB)';
        }

        return $name;
    }

    protected function createReportData()
    {
        return (new ReportData())
            ->setDimensions([t('Hostname'), t('Service Name')])
            ->setValues([t('SLA in %')]);
    }

    protected function createReportRow($row)
    {
        return (new ReportRow())
            ->setDimensions([$row->host_display_name, $row->display_name])
            ->setValues([(float) $row->sla]);
    }
}
