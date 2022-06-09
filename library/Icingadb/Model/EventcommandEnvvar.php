<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

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
            'eventcommand_id'       => t('Eventcommand Envvar Command Id'),
            'envvar_key'            => t('Eventcommand Envvar Key'),
            'environment_id'        => t('Eventcommand Envvar Environment Id'),
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
