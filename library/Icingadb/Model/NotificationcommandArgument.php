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
 * @property string $argument_key
 * @property string $environment_id
 * @property string $properties_checksum
 * @property ?string $argument_value
 * @property ?string $argument_order
 * @property ?string $description
 * @property ?string $argument_key_override
 * @property string $repeat_key
 * @property string $required
 * @property ?string $set_if
 * @property string $skip_key
 */
class NotificationcommandArgument extends Model
{
    public function getTableName()
    {
        return 'notificationcommand_argument';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'notificationcommand_id',
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
            'notificationcommand_id' => t('Notificationcommand Id'),
            'argument_key'           => t('Notificationcommand Argument Name'),
            'environment_id'         => t('Environment Id'),
            'properties_checksum'    => t('Notificationcommand Argument Properties Checksum'),
            'argument_value'         => t('Notificationcommand Argument Value'),
            'argument_order'         => t('Notificationcommand Argument Position'),
            'description'            => t('Notificationcommand Argument Description'),
            'argument_key_override'  => t('Notificationcommand Argument Actual Name'),
            'repeat_key'             => t('Notificationcommand Argument Repeated'),
            'required'               => t('Notificationcommand Argument Required'),
            'set_if'                 => t('Notificationcommand Argument Condition'),
            'skip_key'               => t('Notificationcommand Argument Without Name')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id',
            'notificationcommand_id',
            'environment_id',
            'properties_checksum'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('notificationcommand', Notificationcommand::class);
    }
}
