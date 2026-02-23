<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Common;

use ipl\Sql\Connection;

trait Database
{
    /**
     * Get the connection to the Icinga database
     *
     * @return Connection
     */
    public function getDb(): Connection
    {
        return Backend::getDb();
    }
}
