<?php

namespace Icinga\Module\Eagle\Model;

use Icinga\Module\Eagle\Model\Behavior\Timestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

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
            'state_history_id',
            'event_type',
            'event_time'
        ];
    }

    public function getSortRules()
    {
        return ['event_time DESC'];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Timestamp([
            'event_time'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        // @TODO(el): Add relation for flapping_history_id
        $relations->belongsTo('endpoint', Endpoint::class);
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('host', Host::class)->setJoinType('LEFT');
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
        $relations->belongsTo('state', StateHistory::class)->setJoinType('LEFT');
    }
}
