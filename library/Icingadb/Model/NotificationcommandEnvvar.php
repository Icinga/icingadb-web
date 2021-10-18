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
            'notificationcommand_id',
            'envvar_key',
            'environment_id',
            'properties_checksum',
            'envvar_value'
        ];
    }

    public function getMetaData()
    {
        return [
            'notificationcommand_id' => t('Notificationcommand Envvar Command Id'),
            'envvar_key'             => t('Notificationcommand Envvar Key'),
            'environment_id'         => t('Notificationcommand Envvar Environment Id'),
            'properties_checksum'    => t('Notificationcommand Envvar Properties Checksum'),
            'envvar_value'           => t('Notificationcommand Envvar Value')
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('notificationcommand', Notificationcommand::class);
    }
}
