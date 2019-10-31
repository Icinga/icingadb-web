<?php

namespace Icinga\Module\Eagle\Widget;

use Icinga\Module\Eagle\Common\StateBadges;

class HostStateBadges extends StateBadges
{
    protected function getPrefix()
    {
        return 'hosts';
    }

    protected function assemble()
    {
        $this->add(array_filter([
            $this->createGroup('down'),
            $this->createBadge('unknown'),
            $this->createBadge('up'),
            $this->createBadge('pending')
        ]));
    }
}
