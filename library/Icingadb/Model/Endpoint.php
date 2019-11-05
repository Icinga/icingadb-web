<?php

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class Endpoint extends Model
{
    public function getTableName()
    {
        return 'endpoint';
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
            'zone_id'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('zone', Zone::class);

        $relations->hasMany('host', Host::class)
            ->setForeignKey('command_endpoint_id');
        $relations->hasMany('service', Service::class)
            ->setForeignKey('command_endpoint_id');
    }
}
