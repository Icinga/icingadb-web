<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Hook;

use Icinga\Module\Icingadb\Hook\ActionsHook\ObjectActionsHook;
use Icinga\Module\Icingadb\Model\Host;
use ipl\Web\Widget\Link;

abstract class HostActionsHook extends ObjectActionsHook
{
    /**
     * Assemble and return a list of HTML anchors for the given host
     *
     * @param Host $host
     *
     * @return Link[]
     */
    abstract public function getActionsForObject(Host $host): array;
}
