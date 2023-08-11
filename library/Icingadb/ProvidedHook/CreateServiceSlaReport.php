<?php

namespace Icinga\Module\Icingadb\ProvidedHook;

use Icinga\Authentication\Auth;
use Icinga\Module\Icingadb\Hook\ServiceActionsHook;
use Icinga\Module\Icingadb\Model\Service;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;
use ipl\Web\Widget\Link;

class CreateServiceSlaReport extends ServiceActionsHook
{
    use Translation;

    public function getActionsForObject(Service $service): array
    {
        if (! Auth::getInstance()->hasPermission('reporting/reports')) {
            return [];
        }

        $filter = QueryString::render(Filter::all(
            Filter::equal('service.name', $service->name),
            Filter::equal('host.name', $service->host->name)
        ));

        return [
            new Link(
                $this->translate('Create Service SLA Report'),
                Url::fromPath('reporting/reports/new')->addParams(['filter' => $filter, 'report' => 'service']),
                [
                    'data-icinga-modal'   => true,
                    'data-no-icinga-ajax' => true
                ]
            )
        ];
    }
}
