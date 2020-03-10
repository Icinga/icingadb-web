<?php

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use Icinga\Module\Icingadb\Model\Behavior\Timestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

/**
 * Model for table `history`
 *
 * Please note that using this model will fetch history entries for decommissioned services. To avoid
 * this, the query needs a `history.service_id IS NULL OR history_service.id IS NOT NULL` where.
 */
class History extends Model
{
    public function getTableName()
    {
        return 'history';
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
            'comment_history_id',
            'downtime_history_id',
            'flapping_history_id',
            'notification_history_id',
            'acknowledgement_history_id',
            'state_history_id',
            'event_type',
            'event_time'
        ];
    }

    public function getDefaultSort()
    {
        return 'history.event_time desc';
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Timestamp([
            'event_time'
        ]));
        $behaviors->add(new ReRoute([
            'hostgroup'     => 'host.hostgroup',
            'servicegroup'  => 'service.servicegroup'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        // @TODO(el): Add relation for flapping_history_id
        $relations->belongsTo('endpoint', Endpoint::class);
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('host', Host::class);
        $relations->belongsTo('service', Service::class)->setJoinType('LEFT');
        $relations->belongsTo('comment', CommentHistory::class)
            ->setCandidateKey('comment_history_id')
            ->setForeignKey('comment_id')
            ->setJoinType('LEFT');
        $relations->belongsTo('downtime', DowntimeHistory::class)
            ->setCandidateKey('downtime_history_id')
            ->setForeignKey('downtime_id')
            ->setJoinType('LEFT');
        $relations->belongsTo('notification', NotificationHistory::class)->setJoinType('LEFT');
        $relations->belongsTo('acknowledgement', AcknowledgementHistory::class)->setJoinType('LEFT');
        $relations->belongsTo('state', StateHistory::class)->setJoinType('LEFT');
    }
}
