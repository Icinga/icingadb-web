<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class CheckcommandEnvvar extends Model
{
    public function getTableName()
    {
        return 'checkcommand_envvar';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'checkcommand_id',
            'envvar_key',
            'environment_id',
            'properties_checksum',
            'envvar_value'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'checkcommand_id'       => t('Checkcommand Id'),
            'envvar_key'            => t('Checkcommand Envvar Key'),
            'environment_id'        => t('Environment Id'),
            'properties_checksum'   => t('Checkcommand Properties Checksum'),
            'envvar_value'          => t('Checkcommand Envvar Value')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id',
            'checkcommand_id',
            'environment_id',
            'properties_checksum'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('checkcommand', CheckCommand::class);
    }
}
