<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\HostStates;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\StateBadges;
use ipl\Web\Url;

class HostStateBadges extends StateBadges
{
    protected function getBaseUrl(): Url
    {
        return Links::hosts();
    }

    protected function getType(): string
    {
        return 'host';
    }

    protected function getPrefix(): string
    {
        return 'hosts';
    }

    protected function getStateInt(string $state): int
    {
        return HostStates::int($state);
    }

    protected function assemble()
    {
        $this->addAttributes(['class' => 'host-state-badges']);

        $this->add(array_filter([
            $this->createGroup('down'),
            $this->createBadge('unknown'),
            $this->createBadge('up'),
            $this->createBadge('pending')
        ]));
    }
}
