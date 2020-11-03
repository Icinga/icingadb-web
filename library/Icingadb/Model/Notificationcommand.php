<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class Notificationcommand extends Model
{
    public function getTableName()
    {
        return 'notificationcommand';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'zone_id',
            'environment_id',
            'name_checksum',
            'properties_checksum',
            'name',
            'name_ci',
            'command',
            'timeout'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('zone', Zone::class);

        $relations->belongsToMany('customvar', Customvar::class)
            ->through(NotificationcommandCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->through(NotificationcommandCustomvar::class);
        $relations->belongsToMany('vars', CustomvarFlat::class)
            ->through(NotificationcommandCustomvar::class);

        $relations->hasMany('notification', Notification::class)
            ->setForeignKey('command_id');
        $relations->hasMany('argument', NotificationcommandArgument::class)
            ->setForeignKey('command_id');
        $relations->hasMany('envvar', NotificationcommandEnvvar::class)
            ->setForeignKey('command_id');
    }
}
