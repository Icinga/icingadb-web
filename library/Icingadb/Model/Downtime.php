<?php

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\BoolCast;
use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use Icinga\Module\Icingadb\Model\Behavior\Timestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;
use ipl\Sql\Expression;

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
            'zone_id',
            'duration' => new Expression(
                'CASE WHEN is_flexible = \'y\' THEN flexible_duration ELSE'
                . ' scheduled_end_time - scheduled_start_time END'
            )
        ];
    }

    public function getDefaultSort()
    {
        return ['downtime.is_in_effect', 'downtime.start_time desc'];
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
        $behaviors->add(new ReRoute([
            'hostgroup'     => 'host.hostgroup',
            'servicegroup'  => 'service.servicegroup'
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
