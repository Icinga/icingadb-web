<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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
