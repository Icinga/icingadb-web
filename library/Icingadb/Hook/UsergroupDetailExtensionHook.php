<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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
