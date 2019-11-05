<?php

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class Notification extends Model
{
    public function getTableName()
    {
        return 'notification';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'environment_id',
            'name_checksum',
            'properties_checksum',
            'customvars_checksum',
            'users_checksum',
            'usergroups_checksum',
            'name',
            'name_ci',
            'host_id',
            'service_id',
            'command_id',
            'times_begin',
            'times_end',
            'notification_interval',
            'timeperiod_id',
            'states',
            'types',
            'zone_id'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('host', Host::class);
        $relations->belongsTo('service', Service::class);
        $relations->belongsTo('notificationcommand', Notificationcommand::class)
            ->setCandidateKey('command_id');
        $relations->belongsTo('timeperiod', Timeperiod::class);
        $relations->belongsTo('zone', Zone::class);

        $relations->belongsToMany('customvar', Customvar::class)
            ->setThrough(NotificationCustomvar::class);
        $relations->belongsToMany('user', User::class)
            ->setThrough(NotificationUser::class);
        $relations->belongsToMany('usergroup', Usergroup::class)
            ->setThrough(NotificationUsergroup::class);
    }
}
