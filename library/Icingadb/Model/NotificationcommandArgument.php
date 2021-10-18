<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

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

    public function getMetaData()
    {
        return [
            'notificationcommand_id' => t('Notificationcommand Argument Command Id'),
            'argument_key'           => t('Notificationcommand Argument Key'),
            'environment_id'         => t('Notificationcommand Argument Environment Id'),
            'properties_checksum'    => t('Notificationcommand Argument Properties Checksum'),
            'argument_value'         => t('Notificationcommand Argument Value'),
            'argument_order'         => t('Notificationcommand Argument Order'),
            'description'            => t('Notificationcommand Argument Description'),
            'argument_key_override'  => t('Notificationcommand Argument Key Override'),
            'repeat_key'             => t('Notificationcommand Argument Repeat Key'),
            'required'               => t('Notificationcommand Argument Required'),
            'set_if'                 => t('Notificationcommand Argument Set If'),
            'skip_key'               => t('Notificationcommand Argument Skip Key')
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('notificationcommand', Notificationcommand::class);
    }
}
