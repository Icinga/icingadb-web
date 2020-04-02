<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Compat;

use Icinga\Module\Monitoring\Object\Host;

class CompatHost extends Host
{
    use CompatObject;

    private $legacyColumns = [
        'host_name' => 'name'
    ];
}
