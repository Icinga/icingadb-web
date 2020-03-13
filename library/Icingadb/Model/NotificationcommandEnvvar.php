<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class NotificationcommandEnvvar extends Model
{
    public function getTableName()
    {
        return 'notificationcommand_envvar';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'command_id',
            'envvar_key',
            'environment_id',
            'properties_checksum',
            'envvar_value'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('notificationcommand', Notificationcommand::class)
            ->setCandidateKey('command_id');
    }
}
