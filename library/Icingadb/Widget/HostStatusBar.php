<?php

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\BaseStatusBar;
use ipl\Html\BaseHtmlElement;

class HostStatusBar extends BaseStatusBar
{
    protected function assembleTotal(BaseHtmlElement $total)
    {
        $total->add(sprintf('%d Hosts', $this->summary->hosts_total));
    }

    protected function createStateBadges()
    {
        return new HostStateBadges($this->summary);
    }
}
