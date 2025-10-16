<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

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
 * @property string $is_global
 * @property ?string $parent_id
 * @property int $depth
 */
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
            'environment_id'        => t('Environment Id'),
            'name_checksum'         => t('Zone Name Checksum'),
            'properties_checksum'   => t('Zone Properties Checksum'),
            'name'                  => t('Zone Name'),
            'name_ci'               => t('Zone Name (CI)'),
            'is_global'             => t('Zone Is Global'),
            'parent_id'             => t('Parent Zone Id'),
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
