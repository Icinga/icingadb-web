<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Hook;

use Icinga\Module\Icingadb\Hook\ActionsHook\ObjectActionsHook;
use Icinga\Module\Icingadb\Model\Service;
use ipl\Web\Widget\Link;

abstract class ServiceActionsHook extends ObjectActionsHook
{
    /**
     * Assemble and return a list of HTML anchors for the given service
     *
     * @param Service $service
     *
     * @return Link[]
     */
    abstract public function getActionsForObject(Service $service): array;
}
