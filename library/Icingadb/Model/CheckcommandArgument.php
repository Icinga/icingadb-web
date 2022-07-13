<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class CheckcommandArgument extends Model
{
    public function getTableName()
    {
        return 'checkcommand_argument';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'checkcommand_id',
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
            'checkcommand_id'       => t('Checkcommand Id'),
            'argument_key'          => t('Checkcommand Argument Key'),
            'environment_id'        => t('Environment Id'),
            'properties_checksum'   => t('Checkcommand Argument Properties Checksum'),
            'argument_value'        => t('Checkcommand Argument Value'),
            'argument_order'        => t('Checkcommand Argument Order'),
            'description'           => t('Checkcommand Argument Description'),
            'argument_key_override' => t('Checkcommand Argument Key Override'),
            'repeat_key'            => t('Checkcommand Argument Repeat Key'),
            'required'              => t('Checkcommand Argument Required'),
            'set_if'                => t('Checkcommand Argument Set If'),
            'skip_key'              => t('Checkcommand Argument Skip Key')
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
