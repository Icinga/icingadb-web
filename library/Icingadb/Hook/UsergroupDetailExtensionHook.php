<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Hook;

use Icinga\Module\Icingadb\Hook\ExtensionHook\ObjectDetailExtensionHook;
use Icinga\Module\Icingadb\Model\Usergroup;
use ipl\Html\ValidHtml;

abstract class UsergroupDetailExtensionHook extends ObjectDetailExtensionHook
{
    /**
     * Assemble and return an HTML representation of the given usergroup
     *
     * @param Usergroup $usergroup
     *
     * @return ValidHtml
     */
    abstract public function getHtmlForObject(Usergroup $usergroup): ValidHtml;
}
