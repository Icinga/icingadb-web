<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Common;

use Icinga\Module\Icingadb\Model\Host;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\StateBall;

trait HostLink
{
    protected function createHostLink(Host $host, bool $withStateBall = false): BaseHtmlElement
    {
        $content = [];

        if ($withStateBall) {
            $content[] = new StateBall($host->state->getStateText(), StateBall::SIZE_MEDIUM);
            $content[] = ' ';
        }

        $content[] = $host->display_name;

        return Html::tag('a', ['href' => Links::host($host), 'class' => 'subject'], $content);
    }
}
