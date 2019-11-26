<?php

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\Bitmask;
use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class User extends Model
{
    public function getTableName()
    {
        return 'user';
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
            'groups_checksum',
            'name',
            'name_ci',
            'display_name',
            'email',
            'pager',
            'notifications_enabled',
            'timeperiod_id',
            'states',
            'types',
            'zone_id'
        ];
    }

    public function getSortRules()
    {
        return ['display_name'];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new ReRoute([
            'host'          => 'notification.host',
            'service'       => 'notification.service',
            'hostgroup'     => 'notification.host.hostgroup',
            'servicegroup'  => 'notification.service.servicegroup'
        ]));
        $behaviors->add(new Bitmask([
            'states' => [
                'ok'        => 1,
                'warning'   => 2,
                'critical'  => 4,
                'unknown'   => 8,
                'up'        => 16,
                'down'      => 32
            ],
            'types' => [
                'downtime_start'    => 1,
                'downtime_end'      => 2,
                'downtime_removed'  => 4,
                'custom'            => 8,
                'ack'               => 16,
                'problem'           => 32,
                'recovery'          => 64,
                'flapping_start'    => 128,
                'flapping_end'      => 256
            ]
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('timeperiod', Timeperiod::class);
        $relations->belongsTo('zone', Zone::class);

        $relations->belongsToMany('customvar', Customvar::class)
            ->through(UserCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->through(UserCustomvar::class);
        $relations->belongsToMany('notification', Notification::class)
            ->through(NotificationUser::class);
        $relations->belongsToMany('usergroup', Usergroup::class)
            ->through(UsergroupMember::class);
    }
}
