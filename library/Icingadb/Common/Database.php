<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

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
