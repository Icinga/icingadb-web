<?php

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

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
            'customvars_checksum',
            'name',
            'name_ci',
            'display_name',
            'zone_id'
        ];
    }

    public function getSortRules()
    {
        return ['display_name'];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('zone', Zone::class);

        $relations->belongsToMany('customvar', Customvar::class)
            ->setThrough(HostgroupCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->setThrough(HostgroupCustomvar::class);
        $relations->belongsToMany('host', Host::class)
            ->setThrough(HostgroupMember::class);
        $relations->belongsToMany('service', Service::class)
            ->setThrough(HostgroupMember::class);
    }
}
