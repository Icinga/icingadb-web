<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use ipl\Html\Html;
use ipl\Web\Widget\StateBall;

trait ServiceLink
{
    protected function createServiceLink(Service $service, Host $host, $withStateBall = false)
    {
        $content = [];

        if ($withStateBall) {
            $content[] = new StateBall($service->state->getStateText(), StateBall::SIZE_MEDIUM);
            $content[] = ' ';
        }

        $content[] = $service->display_name;

        return [
            Html::tag('a', ['href' => Links::service($service, $host)], $content),
            ' on ',
            Html::tag(
                'a',
                ['href' => Links::host($host)],
                [
                    new StateBall($host->state->getStateText(), StateBall::SIZE_MEDIUM),
                    ' ',
                    $host->display_name
                ]
            )
        ];
    }
}
