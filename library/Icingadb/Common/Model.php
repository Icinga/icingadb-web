<?php

// SPDX-FileCopyrightText: 2025 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Common;

use ipl\Orm\Query;
use ipl\Sql\Connection;

abstract class Model extends \ipl\Orm\Model
{
    public static function on(Connection $db)
    {
        $query = parent::on($db);

        return $query->on(
            Query::ON_SELECT_ASSEMBLED,
            function () use ($query) {
                $auth = new class () {
                    use Auth;
                };

                $auth->assertColumnRestrictions($query->getFilter());
            }
        );
    }
}
