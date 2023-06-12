<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class SlaHistoryState extends Model
{
    public function getTableName()
    {
        return 'sla_history_state';
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
            'object_type',
            'event_time',
            'hard_state',
            'previous_hard_state'
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id',
            'environment_id',
            'endpoint_id',
            'host_id',
            'service_id'
        ]));

        $behaviors->add(new MillisecondTimestamp(['event_time']));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('host', Host::class);
        $relations->belongsTo('service', Service::class);
    }

    public function getDefaultSort()
    {
        return ['event_time'];
    }
}
