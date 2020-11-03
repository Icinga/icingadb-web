<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

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

    public function getMetaData()
    {
        return [
            'environment_id'        => t('Usergroup Environment Id'),
            'name_checksum'         => t('Usergroup Name Checksum'),
            'properties_checksum'   => t('Usergroup Properties Checksum'),
            'name'                  => t('Usergroup Name'),
            'name_ci'               => t('Usergroup Name (CI)'),
            'display_name'          => t('Usergroup Display Name'),
            'zone_id'               => t('Usergroup Zone Id')
        ];
    }

    public function getSearchColumns()
    {
        return ['name_ci'];
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
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('zone', Zone::class);

        $relations->belongsToMany('customvar', Customvar::class)
            ->through(UsergroupCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->through(UsergroupCustomvar::class);
        $relations->belongsToMany('user', User::class)
            ->through(UsergroupMember::class);
        $relations->belongsToMany('notification', Notification::class)
            ->through('notification_recipient');
    }
}
