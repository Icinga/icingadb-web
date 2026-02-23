<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Hook;

use Icinga\Module\Icingadb\Hook\ExtensionHook\ObjectDetailExtensionHook;
use Icinga\Module\Icingadb\Model\Host;
use ipl\Html\ValidHtml;

abstract class HostDetailExtensionHook extends ObjectDetailExtensionHook
{
    /**
     * Assemble and return an HTML representation of the given host
     *
     * @param Host $host
     *
     * @return ValidHtml
     */
    abstract public function getHtmlForObject(Host $host): ValidHtml;
}
