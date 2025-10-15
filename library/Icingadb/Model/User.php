<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

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
 * @property string $display_name
 * @property string $email
 * @property string $pager
 * @property string $notifications_enabled
 * @property ?string $timeperiod_id
 * @property string[] $states
 * @property string[] $types
 * @property ?string $zone_id
 */
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

    public function getColumnDefinitions()
    {
        return [
            'environment_id'        => t('Environment Id'),
            'name_checksum'         => t('User Name Checksum'),
            'properties_checksum'   => t('User Properties Checksum'),
            'name'                  => t('User Name'),
            'name_ci'               => t('User Name (CI)'),
            'display_name'          => t('User Display Name'),
            'email'                 => t('User Email'),
            'pager'                 => t('User Pager'),
            'notifications_enabled' => t('User Receives Notifications'),
            'timeperiod_id'         => t('Timeperiod Id'),
            'states'                => t('Notification State Filter'),
            'types'                 => t('Notification Type Filter'),
            'zone_id'               => t('Zone Id')
        ];
    }

    public function getSearchColumns()
    {
        return ['name_ci', 'display_name'];
    }

    public function getDefaultSort()
    {
        return 'user.display_name';
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

        $behaviors->add(new Binary([
            'id',
            'environment_id',
            'name_checksum',
            'properties_checksum',
            'timeperiod_id',
            'zone_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('timeperiod', Timeperiod::class)->setJoinType('LEFT');
        $relations->belongsTo('zone', Zone::class);

        $relations->belongsToMany('customvar', Customvar::class)
            ->through(UserCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->through(UserCustomvar::class);
        $relations->belongsToMany('vars', Vars::class)
            ->through(UserCustomvar::class);
        $relations->belongsToMany('notification', Notification::class)
            ->through('notification_recipient');
        $relations->belongsToMany('notification_history', NotificationHistory::class)
            ->through('user_notification_history');
        $relations->belongsToMany('usergroup', Usergroup::class)
            ->through(UsergroupMember::class);
    }
}
