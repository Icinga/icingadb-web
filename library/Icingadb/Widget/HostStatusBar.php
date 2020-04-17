<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\BaseStatusBar;
use ipl\Html\BaseHtmlElement;

class HostStatusBar extends BaseStatusBar
{
    protected function assembleTotal(BaseHtmlElement $total)
    {
        $total->add(sprintf(tp('%d Host', '%d Hosts', $this->summary->hosts_total), $this->summary->hosts_total));
    }

    protected function createStateBadges()
    {
        return new HostStateBadges($this->summary);
    }
}
