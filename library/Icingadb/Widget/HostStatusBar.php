<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\BaseStatusBar;
use ipl\Html\BaseHtmlElement;

class HostStatusBar extends BaseStatusBar
{
    protected function assembleTotal(BaseHtmlElement $total): void
    {
        $total->add(sprintf(tp('%d Host', '%d Hosts', $this->summary->hosts_total), $this->summary->hosts_total));
    }

    protected function createStateBadges(): BaseHtmlElement
    {
        return (new HostStateBadges($this->summary))->setBaseFilter($this->getBaseFilter());
    }
}
