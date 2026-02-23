<?php

// SPDX-FileCopyrightText: 2024 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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
        $this->addAttributes(['class' => 'dependency-node-state-badges']);

        $this->add(array_filter([
            $this->createGroup('problem'),
            $this->createGroup('warning'),
            $this->createGroup('unknown'),
            $this->createBadge('ok'),
            $this->createBadge('pending')
        ]));
    }
}
