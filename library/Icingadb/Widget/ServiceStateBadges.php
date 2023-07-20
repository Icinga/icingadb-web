<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\ServiceStates;
use Icinga\Module\Icingadb\Common\StateBadges;
use ipl\Web\Url;

class ServiceStateBadges extends StateBadges
{
    protected function getBaseUrl(): Url
    {
        return Links::services();
    }

    protected function getType(): string
    {
        return 'service';
    }

    protected function getPrefix(): string
    {
        return 'services';
    }

    protected function getStateInt(string $state): int
    {
        return ServiceStates::int($state);
    }

    protected function assemble()
    {
        $this->addAttributes(['class' => 'service-state-badges']);

        $this->add(array_filter([
            $this->createGroup('critical'),
            $this->createGroup('warning'),
            $this->createGroup('unknown'),
            $this->createBadge('ok'),
            $this->createBadge('pending')
        ]));
    }
}
