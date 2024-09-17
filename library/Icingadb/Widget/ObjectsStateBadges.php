<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\StateBadges;

/**
 * State badges for the objects
 */
class ObjectsStateBadges extends StateBadges
{
    protected function getType(): string
    {
        return 'objects';
    }

    protected function getPrefix(): string
    {
        return 'objects';
    }

    protected function assemble(): void
    {
        $this->addAttributes(['class' => 'objects-state-badges']);

        $this->add(array_filter([
            $this->createGroup('problem'),
            $this->createGroup('warning'),
            $this->createGroup('unknown'),
            $this->createBadge('ok'),
            $this->createBadge('pending')
        ]));
    }
}
