<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use Icinga\Module\Icingadb\Common\Model;
use ipl\Orm\Relations;

/**
 * @property string $id
 * @property string $eventcommand_id
 * @property string $envvar_key
 * @property string $environment_id
 * @property string $properties_checksum
 * @property string $envvar_value
 */
class EventcommandEnvvar extends Model
{
    public function getTableName()
    {
        return 'eventcommand_envvar';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'eventcommand_id',
            'envvar_key',
            'environment_id',
            'properties_checksum',
            'envvar_value'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'eventcommand_id'       => t('Eventcommand Id'),
            'envvar_key'            => t('Eventcommand Envvar Name'),
            'environment_id'        => t('Environment Id'),
            'properties_checksum'   => t('Eventcommand Envvar Properties Checksum'),
            'envvar_value'          => t('Eventcommand Envvar Value')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id',
            'eventcommand_id',
            'environment_id',
            'properties_checksum'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('eventcommand', Eventcommand::class);
    }
}
