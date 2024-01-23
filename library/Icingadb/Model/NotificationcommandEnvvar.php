<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

/**
 * @property string $id
 * @property string $notificationcommand_id
 * @property string $envvar_key
 * @property string $environment_id
 * @property string $properties_checksum
 * @property string $envvar_value
 */
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

    public function getColumnDefinitions()
    {
        return [
            'notificationcommand_id' => t('Notificationcommand Id'),
            'envvar_key'             => t('Notificationcommand Envvar Key'),
            'environment_id'         => t('Environment Id'),
            'properties_checksum'    => t('Notificationcommand Envvar Properties Checksum'),
            'envvar_value'           => t('Notificationcommand Envvar Value')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id',
            'notificationcommand_id',
            'environment_id',
            'properties_checksum',
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('notificationcommand', Notificationcommand::class);
    }
}
