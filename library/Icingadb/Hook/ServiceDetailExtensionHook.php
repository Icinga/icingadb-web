<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Hook;

use Icinga\Module\Icingadb\Hook\ExtensionHook\ObjectDetailExtensionHook;
use Icinga\Module\Icingadb\Model\Service;
use ipl\Html\ValidHtml;

abstract class ServiceDetailExtensionHook extends ObjectDetailExtensionHook
{
    /**
     * Assemble and return an HTML representation of the given service
     *
     * @param Service $service
     *
     * @return ValidHtml
     */
    abstract public function getHtmlForObject(Service $service): ValidHtml;
}
