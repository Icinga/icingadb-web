<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Module\Icingadb\Lib;

use Icinga\Module\Icingadb\Forms\GeneralConfigForm as BaseGeneralConfigForm;

class GeneralConfigForm extends BaseGeneralConfigForm
{
    public function detectIcingaweb2Url(): string
    {
        return parent::detectIcingaweb2Url();
    }

    protected function getBasePath(): string
    {
        return '/icingaweb2';
    }
}
