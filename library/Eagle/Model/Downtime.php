<?php

namespace Icinga\Module\Eagle\Model;

use Icinga\Module\Eagle\Model\Behavior\BoolCast;
use Icinga\Module\Eagle\Model\Behavior\Timestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Downtime extends Model
{
    public function getTableName()
    {
        return 'downtime';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'environment_id',
            'object_type',
            'host_id',
            'service_id',
            'name_checksum',
            'properties_checksum',
            'name',
            'author',
            'comment',
            'entry_time',
            'scheduled_start_time',
            'scheduled_end_time',
            'flexible_duration',
            'is_flexible',
            'is_in_effect',
            'start_time',
            'end_time',
            'zone_id'
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new BoolCast([
            'is_flexible',
            'is_in_effect'
        ]));
        $behaviors->add(new Timestamp([
            'entry_time',
            'scheduled_start_time',
            'scheduled_end_time',
            'flexible_duration',
            'start_time',
            'end_time'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('host', Host::class)->setJoinType('LEFT');
        $relations->belongsTo('service', Service::class)->setJoinType('LEFT');
        $relations->belongsTo('zone', Zone::class);
    }
}
