<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Hook;

use Icinga\Module\Icingadb\Hook\ExtensionHook\ObjectDetailExtensionHook;
use Icinga\Module\Icingadb\Model\User;
use ipl\Html\ValidHtml;

abstract class UserDetailExtensionHook extends ObjectDetailExtensionHook
{
    /**
     * Assemble and return an HTML representation of the given user
     *
     * @param User $user
     *
     * @return ValidHtml
     */
    abstract public function getHtmlForObject(User $user): ValidHtml;
}
