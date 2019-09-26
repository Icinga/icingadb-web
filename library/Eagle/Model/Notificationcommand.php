<?php

namespace Icinga\Module\Eagle\Model;

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
            ->setThrough(NotificationcommandCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->setThrough(NotificationcommandCustomvar::class);

        $relations->hasMany('notification', Notification::class)
            ->setForeignKey('command_id');
        $relations->hasMany('argument', NotificationcommandArgument::class)
            ->setForeignKey('command_id');
        $relations->hasMany('envvar', NotificationcommandEnvvar::class)
            ->setForeignKey('command_id');
    }
}
