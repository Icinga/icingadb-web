<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\BaseStatusBar;
use ipl\Html\BaseHtmlElement;

class ServiceStatusBar extends BaseStatusBar
{
    protected function assembleTotal(BaseHtmlElement $total): void
    {
        $total->add(sprintf(
            tp('%d Service', '%d Services', $this->summary->services_total),
            $this->summary->services_total
        ));
    }

    protected function createStateBadges(): BaseHtmlElement
    {
        return (new ServiceStateBadges($this->summary))->setBaseFilter($this->getBaseFilter());
    }
}
