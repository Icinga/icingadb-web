<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

/**
 * Model for table `sla_history_downtime`
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
class SlaHistoryDowntime extends Model
{
    public function getTableName()
    {
        return 'sla_history_downtime';
    }

    public function getKeyName()
    {
        return ['host_id', 'service_id'];
    }

    public function getColumns()
    {
        return [
            'environment_id',
            'endpoint_id',
            'object_type',
            'host_id',
            'service_id',
            'downtime_id',
            'downtime_start',
            'downtime_end'
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new MillisecondTimestamp([
            'downtime_start',
            'downtime_end'
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
        $relations->belongsTo('downtime', Downtime::class)->setJoinType('LEFT');
    }
}
