<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\StateBadges;

/**
 * State badges for the dependency nodes
 */
class DependencyNodeStateBadges extends StateBadges
{
    protected function getType(): string
    {
        return 'nodes';
    }

    protected function getPrefix(): string
    {
        return 'nodes';
    }

    protected function assemble(): void
    {
        $this->addAttributes(['class' => 'nodes-state-badges']);

        $this->add(array_filter([
            $this->createGroup('problem'),
            $this->createGroup('warning'),
            $this->createGroup('unknown'),
            $this->createBadge('ok'),
            $this->createBadge('pending')
        ]));
    }
}
