<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

/**
 * @property string $id
 * @property string $environment_id
 * @property string $name_checksum
 * @property string $properties_checksum
 * @property string $name
 * @property string $name_ci
 * @property string $display_name
 * @property string $prefer_includes
 * @property ?string $zone_id
 */
class Timeperiod extends Model
{
    public function getTableName()
    {
        return 'timeperiod';
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
            'prefer_includes',
            'zone_id'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'environment_id'        => t('Environment Id'),
            'name_checksum'         => t('Timeperiod Name Checksum'),
            'properties_checksum'   => t('Timeperiod Properties Checksum'),
            'name'                  => t('Timeperiod Name'),
            'name_ci'               => t('Timeperiod Name (CI)'),
            'display_name'          => t('Timeperiod Display Name'),
            'prefer_includes'       => t('Timeperiod Prefer Includes'),
            'zone_id'               => t('Zone Id')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new ReRoute([
            'hostgroup'     => 'host.hostgroup',
            'servicegroup'  => 'service.servicegroup'
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
            ->through(TimeperiodCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->through(TimeperiodCustomvar::class);
        $relations->belongsToMany('vars', Vars::class)
            ->through(TimeperiodCustomvar::class);

        // TODO: Decide how to establish the override relations

        $relations->hasMany('range', TimeperiodRange::class);
        $relations->hasMany('host', Host::class)
            ->setForeignKey('check_timeperiod_id');
        $relations->hasMany('Notification', Notification::class);
        $relations->hasMany('service', Service::class)
            ->setForeignKey('check_timeperiod_id');
        $relations->hasMany('user', User::class);
        $relations->hasMany('dependency', Dependency::class);
    }
}
