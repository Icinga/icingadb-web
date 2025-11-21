<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

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
