<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Icinga\Module\Icingadb\Model\Host;
use ipl\Html\Html;
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

        return Html::tag('a', ['href' => Links::host($host)], $content);
    }
}
