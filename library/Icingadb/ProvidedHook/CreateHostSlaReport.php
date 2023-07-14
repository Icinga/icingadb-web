<?php

namespace Icinga\Module\Icingadb\ProvidedHook;

use Icinga\Authentication\Auth;
use Icinga\Module\Icingadb\Hook\HostActionsHook;
use Icinga\Module\Icingadb\Model\Host;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;
use ipl\Web\Widget\Link;

class CreateHostSlaReport extends HostActionsHook
{
    use Translation;

    public function getActionsForObject(Host $host): array
    {
        if (! Auth::getInstance()->hasPermission('reporting/reports')) {
            return [];
        }

        $filter = QueryString::render(Filter::equal('host.name', $host->name));

        return [
            new Link(
                $this->translate('Create Host SLA Report'),
                Url::fromPath('reporting/reports/new')->addParams(['filter' => $filter, 'report' => 'host'])
            )
        ];
    }
}
