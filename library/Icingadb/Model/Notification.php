<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\Bitmask;
use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use Icinga\Module\Icingadb\Common\Model;
use ipl\Orm\Relations;

/**
 * @property string $id
 * @property string $environment_id
 * @property string $name_checksum
 * @property string $properties_checksum
 * @property string $name
 * @property string $name_ci
 * @property string $host_id
 * @property ?string $service_id
 * @property string $notificationcommand_id
 * @property ?int $times_begin
 * @property ?int $times_end
 * @property int $notification_interval
 * @property ?string $timeperiod_id
 * @property string[] $states
 * @property string[] $types
 * @property ?string $zone_id
 */
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

    public function getColumnDefinitions()
    {
        return [
            'environment_id'         => t('Environment Id'),
            'name_checksum'          => t('Notification Name Checksum'),
            'properties_checksum'    => t('Notification Properties Checksum'),
            'name'                   => t('Notification Name'),
            'name_ci'                => t('Notification Name (CI)'),
            'host_id'                => t('Host Id'),
            'service_id'             => t('Service Id'),
            'notificationcommand_id' => t('Notificationcommand Id'),
            'times_begin'            => t('Notification Escalate After'),
            'times_end'              => t('Notification Escalate Until'),
            'notification_interval'  => t('Notification Interval'),
            'timeperiod_id'          => t('Timeperiod Id'),
            'states'                 => t('Notification State Filter'),
            'types'                  => t('Notification Type Filter'),
            'zone_id'                => t('Zone Id')
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

        $behaviors->add(new Binary([
            'id',
            'environment_id',
            'name_checksum',
            'properties_checksum',
            'host_id',
            'service_id',
            'notificationcommand_id',
            'timeperiod_id',
            'zone_id'
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
            ->through(NotificationCustomvar::class)
            ->setThroughAlias('t_notification_customvar');
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->through(NotificationCustomvar::class);
        $relations->belongsToMany('vars', Vars::class)
            ->through(NotificationCustomvar::class);
        $relations->belongsToMany('user', User::class)
            ->through('notification_recipient');
        $relations->belongsToMany('usergroup', Usergroup::class)
            ->through('notification_recipient');
    }
}
