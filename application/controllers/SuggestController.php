<?php

// SPDX-FileCopyrightText: 2025 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use ipl\Web\Compat\CompatController;

class SuggestController extends CompatController
{
    public function restrictionColumnAction(): void
    {
        $suggestions = (new ObjectSuggestions())
            ->setModel(Host::class)
            ->onlyWithCustomVarSources(['host', 'service'])
            ->withFixedColumns([
                'host.name' => $this->translate('Host Name'),
                'hostgroup.name' => $this->translate('Hostgroup Name'),
                'host.user.name' => $this->translate('Contact Name'),
                'host.usergroup.name' => $this->translate('Contactgroup Name'),
                'service.name' => $this->translate('Service Name'),
                'servicegroup.name' => $this->translate('Servicegroup Name'),
                'service.user.name' => $this->translate('Contact Name'),
                'service.usergroup.name' => $this->translate('Contactgroup Name')
            ]);

        $this->getDocument()->addHtml(
            $suggestions->forRequest($this->getServerRequest())
        );
    }
}
