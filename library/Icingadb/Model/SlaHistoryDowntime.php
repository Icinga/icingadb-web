<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class SlaHistoryDowntime extends Model
{
    public function getTableName()
    {
        return 'sla_history_downtime';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'environment_id',
            'endpoint_id',
            'host_id',
            'service_id',
            'downtime_id',
            'object_type',
            'downtime_start',
            'downtime_end'
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id',
            'environment_id',
            'endpoint_id',
            'host_id',
            'service_id',
            'downtime_id'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'downtime_start',
            'downtime_end'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('host', Host::class);
        $relations->belongsTo('service', Service::class);
    }
}
