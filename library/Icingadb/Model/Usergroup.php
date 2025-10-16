<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

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
 * @property ?string $zone_id
 */
class Usergroup extends Model
{
    public function getTableName()
    {
        return 'usergroup';
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
            'zone_id'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'environment_id'        => t('Environment Id'),
            'name_checksum'         => t('Contactgroup Name Checksum'),
            'properties_checksum'   => t('Contactgroup Properties Checksum'),
            'name'                  => t('Contactgroup Name'),
            'name_ci'               => t('Contactgroup Name (CI)'),
            'display_name'          => t('Contactgroup Display Name'),
            'zone_id'               => t('Zone Id')
        ];
    }

    public function getSearchColumns()
    {
        return ['name_ci', 'display_name'];
    }

    public function getDefaultSort()
    {
        return 'usergroup.display_name';
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new ReRoute([
            'host'          => 'notification.host',
            'service'       => 'notification.service',
            'hostgroup'     => 'notification.host.hostgroup',
            'servicegroup'  => 'notification.service.servicegroup'
        ]));

        $behaviors->add(new Binary([
            'id',
            'environment_id',
            'name_checksum',
            'properties_checksum',
            'zone_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('zone', Zone::class);

        $relations->belongsToMany('customvar', Customvar::class)
            ->through(UsergroupCustomvar::class)
            ->setThroughAlias('t_usergroup_customvar');
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->through(UsergroupCustomvar::class);
        $relations->belongsToMany('vars', Vars::class)
            ->through(UsergroupCustomvar::class);
        $relations->belongsToMany('user', User::class)
            ->through(UsergroupMember::class);
        $relations->belongsToMany('notification', Notification::class)
            ->through('notification_recipient');
    }
}
