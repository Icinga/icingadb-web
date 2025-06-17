<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\ProvidedHook\Reporting;

use Icinga\Application\Icinga;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Reporting\Timerange;
use ipl\Sql\Expression;
use ipl\Stdlib\Filter\Rule;

use function ipl\I18n\t;

class HostSlaChartReport extends SlaChartReport
{
    public function getName()
    {
        $name = t('Host SLA Chart');
        if (Icinga::app()->getModuleManager()->hasEnabled('idoreports')) {
            $name .= ' (Icinga DB)';
        }

        return $name;
    }

    protected function fetchSla(Timerange $timerange, Rule $filter = null)
    {
        $sla = Host::on($this->getDb())
            ->columns([
                'id',
                'display_name',
                'sla' => new Expression(sprintf(
                    "get_sla_ok_percent(%s, NULL, '%s', '%s')",
                    'host.id',
                    $timerange->getStart()->format('Uv'),
                    $timerange->getEnd()->format('Uv')
                )),
            ]);

        $this->applyRestrictions($sla);

        if ($filter !== null) {
            $sla->filter($filter);
        }

        return $sla;
    }
}
