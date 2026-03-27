<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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
            'caPath',
            'username',
            'password'
        ]
    ];
}
