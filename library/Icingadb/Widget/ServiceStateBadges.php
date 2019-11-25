<?php

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\ServiceStates;
use Icinga\Module\Icingadb\Common\StateBadges;

class ServiceStateBadges extends StateBadges
{
    protected function getBaseUrl()
    {
        return Links::services();
    }

    protected function getPrefix()
    {
        return 'services';
    }

    protected function getStateInt($state)
    {
        return ServiceStates::int($state);
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
