<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\ProvidedHook\Reporting;

use Icinga\Application\Icinga;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Reporting\Timerange;
use ipl\Sql\Expression;
use ipl\Stdlib\Filter\Rule;

use function ipl\I18n\t;

class ServiceSlaChartReport extends SlaChartReport
{
    public function getName()
    {
        $name = t('Service SLA Chart');
        if (Icinga::app()->getModuleManager()->hasEnabled('idoreports')) {
            $name .= ' (Icinga DB)';
        }

        return $name;
    }

    protected function fetchSla(Timerange $timerange, Rule $filter = null)
    {
        $sla = Service::on($this->getDb())
            ->columns([
                'id',
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
