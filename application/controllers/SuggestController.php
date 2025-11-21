<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\ProvidedHook\Notifications\V1\Source;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use ipl\Web\Compat\CompatController;

class SuggestController extends CompatController
{
    public function restrictionColumnAction(): void
    {
        $suggestions = (new ObjectSuggestions())
            ->setModel(
                match ($this->params->getRequired('type')) {
                    'host', Source::TYPE_ALL => Host::class,
                    'service' => Service::class,
                    default => $this->httpBadRequest('Invalid type')
                }
            )
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
