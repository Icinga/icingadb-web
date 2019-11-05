<?php

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class Servicegroup extends Model
{
    public function getTableName()
    {
        return 'servicegroup';
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
            ->setThrough(ServicegroupCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->setThrough(ServicegroupCustomvar::class);
        $relations->belongsToMany('service', Service::class)
            ->setThrough(ServicegroupMember::class);
    }
}
