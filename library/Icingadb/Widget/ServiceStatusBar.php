<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\BaseStatusBar;
use ipl\Html\BaseHtmlElement;

class ServiceStatusBar extends BaseStatusBar
{
    protected function assembleTotal(BaseHtmlElement $total)
    {
        $total->add(sprintf('%d Services', $this->summary->services_total));
    }

    protected function createStateBadges()
    {
        return new ServiceStateBadges($this->summary);
    }
}
