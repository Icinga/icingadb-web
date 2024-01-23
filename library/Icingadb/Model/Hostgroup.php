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
 * @property string $zone_id
 */
class Hostgroup extends Model
{
    public function getTableName()
    {
        return 'hostgroup';
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
            'name_checksum'         => t('Hostgroup Name Checksum'),
            'properties_checksum'   => t('Hostgroup Properties Checksum'),
            'name'                  => t('Hostgroup Name'),
            'name_ci'               => t('Hostgroup Name (CI)'),
            'display_name'          => t('Hostgroup Display Name'),
            'zone_id'               => t('Zone Id')
        ];
    }

    public function getSearchColumns()
    {
        return ['name_ci', 'display_name'];
    }

    public function getDefaultSort()
    {
        return 'display_name';
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new ReRoute([
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
            ->through(HostgroupCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->through(HostgroupCustomvar::class);
        $relations->belongsToMany('vars', Vars::class)
            ->through(HostgroupCustomvar::class);
        $relations->belongsToMany('host', Host::class)
            ->through(HostgroupMember::class);
        $relations->belongsToMany('service', Service::class)
            ->through(HostgroupMember::class);
    }
}
