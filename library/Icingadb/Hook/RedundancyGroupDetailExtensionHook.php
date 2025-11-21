<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Hook;

use Icinga\Module\Icingadb\Hook\ExtensionHook\ObjectDetailExtensionHook;
use Icinga\Module\Icingadb\Model\RedundancyGroup;
use ipl\Html\ValidHtml;

abstract class RedundancyGroupDetailExtensionHook extends ObjectDetailExtensionHook
{
    /**
     * Assemble and return an HTML representation of the given redundancy group
     *
     * @param RedundancyGroup $redundancyGroup
     *
     * @return ValidHtml
     */
    abstract public function getHtmlForObject(RedundancyGroup $redundancyGroup): ValidHtml;
}
