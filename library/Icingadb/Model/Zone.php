<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Zone extends Model
{
    public function getTableName()
    {
        return 'zone';
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
            'is_global',
            'parent_id',
            'depth'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'environment_id'        => t('Zone Environment Id'),
            'name_checksum'         => t('Zone Name Checksum'),
            'properties_checksum'   => t('Zone Properties Checksum'),
            'name'                  => t('Zone Name'),
            'name_ci'               => t('Zone Name (CI)'),
            'is_global'             => t('Zone Is Global'),
            'parent_id'             => t('Zone Parent Id'),
            'depth'                 => t('Zone Depth')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id',
            'environment_id',
            'name_checksum',
            'properties_checksum',
            'parent_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);

        $relations->hasMany('comment', Comment::class);
        $relations->hasMany('downtime', Downtime::class);
        $relations->hasMany('endpoint', Endpoint::class);
        $relations->hasMany('eventcommand', Eventcommand::class);
        $relations->hasMany('host', Host::class);
        $relations->hasMany('hostgroup', Hostgroup::class);
        $relations->hasMany('notification', Notification::class);
        $relations->hasMany('service', Service::class);
        $relations->hasMany('servicegroup', Servicegroup::class);
        $relations->hasMany('timeperiod', Timeperiod::class);
        $relations->hasMany('user', User::class);
        $relations->hasMany('usergroup', Usergroup::class);

        // TODO: Decide how to establish recursive relations
    }
}
