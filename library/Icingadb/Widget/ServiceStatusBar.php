<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2+ */

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
