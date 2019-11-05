<?php

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\StateBadges;

class ServiceStateBadges extends StateBadges
{
    protected function getPrefix()
    {
        return 'services';
    }

    protected function assemble()
    {
        $this->add(array_filter([
            $this->createGroup('critical'),
            $this->createGroup('warning'),
            $this->createGroup('unknown'),
            $this->createBadge('ok'),
            $this->createBadge('pending')
        ]));
    }
}
