<?php

namespace Icinga\Module\Eagle\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class HostDowntime extends Model
{
    public function getTableName()
    {
        return 'host_downtime';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'environment_id',
            'host_id',
            'name_checksum',
            'properties_checksum',
            'name',
            'author',
            'comment',
            'entry_time',
            'scheduled_start_time',
            'scheduled_end_time',
            'duration',
            'is_fixed',
            'is_in_effect',
            'actual_start_time',
            'actual_end_time',
            'zone_id'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('host', Host::class);
        $relations->belongsTo('zone', Zone::class);
    }
}
