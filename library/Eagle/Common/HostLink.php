<?php

namespace Icinga\Module\Eagle\Common;

use Icinga\Module\Eagle\Model\Host;
use ipl\Html\Html;
use ipl\Web\Url;
use ipl\Web\Widget\StateBall;

trait HostLink
{
    protected function createHostLink(Host $host, $withStateBall = false)
    {
        $content = [];

        if ($withStateBall) {
            $content[] = new StateBall($host->state->getStateText(), StateBall::SIZE_MEDIUM);
            $content[] = ' ';
        }

        $content[] = $host->display_name;

        return Html::tag(
            'a',
            [
                'href' => Url::fromPath('eagle/host', ['name' => $host->name])
            ],
            $content
        );
    }
}
