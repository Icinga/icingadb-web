<?php

namespace Icinga\Module\Eagle\Common;

use Icinga\Module\Eagle\Model\Host;
use Icinga\Module\Eagle\Model\Service;
use ipl\Html\Html;
use ipl\Web\Url;
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
            Html::tag(
                'a',
                [
                    'href'  => Url::fromPath('eagle/service', [
                        'name'      => $service->name,
                        'host_name' => $host->name
                    ])
                ],
                $content
            ),
            ' on ',
            Html::tag(
                'a',
                [
                    'href' => Url::fromPath('eagle/host', ['name' => $host->name])
                ],
                [
                    new StateBall($host->state->getStateText(), StateBall::SIZE_MEDIUM),
                    ' ',
                    $host->display_name
                ]
            )
        ];
    }
}
