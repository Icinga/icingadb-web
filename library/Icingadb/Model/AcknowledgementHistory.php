<?php

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\BoolCast;
use Icinga\Module\Icingadb\Model\Behavior\Timestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

/**
 * Model for table `acknowledgement_history`
 *
 * Please note that using this model will fetch history entries for decommissioned services. To avoid this, the query
 * needs a `acknowledgement_history.service_id IS NULL OR acknowledgement_history_service.id IS NOT NULL` where.
 */
class AcknowledgementHistory extends Model
{
    public function getTableName()
    {
        return 'acknowledgement_history';
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
            'set_time',
            'clear_time',
            'author',
            'cleared_by',
            'comment',
            'expire_time',
            'is_sticky',
            'is_persistent'
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new BoolCast([
            'is_sticky',
            'is_persistent'
        ]));

        $behaviors->add(new Timestamp([
            'set_time',
            'clear_time',
            'expire_time'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('endpoint', Endpoint::class);
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('host', Host::class);
        $relations->belongsTo('service', Service::class)->setJoinType('LEFT');
    }
}
