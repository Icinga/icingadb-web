<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class EventcommandArgument extends Model
{
    public function getTableName()
    {
        return 'eventcommand_argument';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'eventcommand_id',
            'argument_key',
            'environment_id',
            'properties_checksum',
            'argument_value',
            'argument_order',
            'description',
            'argument_key_override',
            'repeat_key',
            'required',
            'set_if',
            'skip_key'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'eventcommand_id'       => t('Eventcommand Id'),
            'argument_key'          => t('Eventcommand Argument Name'),
            'environment_id'        => t('Environment Id'),
            'properties_checksum'   => t('Eventcommand Argument Properties Checksum'),
            'argument_value'        => t('Eventcommand Argument Value'),
            'argument_order'        => t('Eventcommand Argument Position'),
            'description'           => t('Eventcommand Argument Description'),
            'argument_key_override' => t('Eventcommand Argument Actual Name'),
            'repeat_key'            => t('Eventcommand Argument Repeated'),
            'required'              => t('Eventcommand Argument Required'),
            'set_if'                => t('Eventcommand Argument Condition'),
            'skip_key'              => t('Eventcommand Argument Without Name')
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
