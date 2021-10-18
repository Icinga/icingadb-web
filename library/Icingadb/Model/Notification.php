<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\Bitmask;
use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behaviors;
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
            'name',
            'name_ci',
            'host_id',
            'service_id',
            'notificationcommand_id',
            'times_begin',
            'times_end',
            'notification_interval',
            'timeperiod_id',
            'states',
            'types',
            'zone_id'
        ];
    }

    public function getMetaData()
    {
        return [
            'environment_id'         => t('Notification Environment Id'),
            'name_checksum'          => t('Notification Name Checksum'),
            'properties_checksum'    => t('Notification Properties Checksum'),
            'name'                   => t('Notification Name'),
            'name_ci'                => t('Notification Name (CI)'),
            'host_id'                => t('Notification Host Id'),
            'service_id'             => t('Notification Service Id'),
            'notificationcommand_id' => t('Notification Command Id'),
            'times_begin'            => t('Notification Times Begin'),
            'times_end'              => t('Notification Times End'),
            'notification_interval'  => t('Notification Interval'),
            'timeperiod_id'          => t('Notification Timeperiod Id'),
            'states'                 => t('Notification States'),
            'types'                  => t('Notification Types'),
            'zone_id'                => t('Notification Zone Id')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new ReRoute([
            'hostgroup'     => 'host.hostgroup',
            'servicegroup'  => 'service.servicegroup'
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
        $relations->belongsTo('host', Host::class);
        $relations->belongsTo('service', Service::class);
        $relations->belongsTo('notificationcommand', Notificationcommand::class);
        $relations->belongsTo('timeperiod', Timeperiod::class);
        $relations->belongsTo('zone', Zone::class);

        $relations->belongsToMany('customvar', Customvar::class)
            ->through(NotificationCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->through(NotificationCustomvar::class);
        $relations->belongsToMany('vars', CustomvarFlat::class)
            ->through(NotificationCustomvar::class);
        $relations->belongsToMany('user', User::class)
            ->through('notification_recipient');
        $relations->belongsToMany('usergroup', Usergroup::class)
            ->through('notification_recipient');
    }
}
