<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Command\Transport;

use Icinga\Repository\IniRepository;

class CommandTransportConfig extends IniRepository
{
    protected $configs = [
        'transport' => [
            'name'      => 'commandtransports',
            'module'    => 'icingadb',
            'keyColumn' => 'name'
        ]
    ];

    protected $queryColumns = [
        'transport' => [
            'name',
            'transport',

            // API options
            'host',
            'port',
            'username',
            'password'
        ]
    ];
}
