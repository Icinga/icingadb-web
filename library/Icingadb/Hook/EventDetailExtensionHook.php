<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Hook;

use Icinga\Module\Icingadb\Hook\ExtensionHook\ObjectDetailExtensionHook;
use Icinga\Module\Icingadb\Model\History;
use ipl\Html\ValidHtml;

abstract class EventDetailExtensionHook extends ObjectDetailExtensionHook
{
    /**
     * Assemble and return an HTML representation of the given event
     *
     * @param History $event
     *
     * @return ValidHtml
     */
    abstract public function getHtmlForObject(History $event): ValidHtml;
}
