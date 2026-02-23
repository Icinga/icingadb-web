<?php

// SPDX-FileCopyrightText: 2023 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Module\Icingadb\Lib;

use Icinga\Module\Icingadb\Util\PerfDataSet;

class PerfdataSetWithPublicData extends PerfdataSet
{
    public $perfdata = [];
}
