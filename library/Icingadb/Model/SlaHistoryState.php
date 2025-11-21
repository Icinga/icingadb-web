<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use Icinga\Module\Icingadb\Common\Model;
use ipl\Orm\Relations;

/**
 * Model for table `sla_history_state`
 *
 * @property string $environment_id
 * @property ?string $endpoint_id
 * @property string $object_type
 * @property string $host_id
 * @property ?string $service_id
 * @property string $downtime_id
 * @property string $downtime_start
 * @property string $downtime_end
 */
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
            'object_type',
            'host_id',
            'service_id',
            'event_time',
            'hard_state',
            'previous_hard_state'
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new MillisecondTimestamp([
            'event_time'
        ]));

        $behaviors->add(new Binary([
            'environment_id',
            'endpoint_id',
            'host_id',
            'service_id',
            'downtime_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('endpoint', Endpoint::class);
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('host', Host::class)->setJoinType('LEFT');
        $relations->belongsTo('service', Service::class)->setJoinType('LEFT');
    }
}
