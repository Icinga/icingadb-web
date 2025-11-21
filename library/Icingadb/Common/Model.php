<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2+ */

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
