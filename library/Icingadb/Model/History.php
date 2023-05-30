<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
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

    public function getColumnDefinitions()
    {
        return [
            'environment_id'    => t('Environment Id'),
            'endpoint_id'       => t('Endpoint Id'),
            'object_type'       => t('Object Type'),
            'host_id'           => t('Host Id'),
            'service_id'        => t('Service Id'),
            'event_type'        => t('Event Type'),
            'event_time'        => t('Event Time')
        ];
    }

    public function getDefaultSort()
    {
        return 'history.event_time desc';
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new MillisecondTimestamp([
            'event_time'
        ]));

        $behaviors->add(new Binary([
            'id',
            'environment_id',
            'endpoint_id',
            'host_id',
            'service_id',
            'comment_history_id',
            'downtime_history_id',
            'flapping_history_id',
            'notification_history_id',
            'acknowledgement_history_id',
            'state_history_id'
        ]));

        $behaviors->add(new ReRoute([
            'hostgroup'     => 'host.hostgroup',
            'servicegroup'  => 'service.servicegroup'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('endpoint', Endpoint::class);
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('host', Host::class);
        $relations->belongsTo('service', Service::class)->setJoinType('LEFT');

        $relations->hasOne('comment', CommentHistory::class)
            ->setCandidateKey('comment_history_id')
            ->setForeignKey('comment_id')
            ->setJoinType('LEFT');
        $relations->hasOne('downtime', DowntimeHistory::class)
            ->setCandidateKey('downtime_history_id')
            ->setForeignKey('downtime_id')
            ->setJoinType('LEFT');
        $relations->hasOne('flapping', FlappingHistory::class)
            ->setCandidateKey('flapping_history_id')
            ->setForeignKey('id')
            ->setJoinType('LEFT');
        $relations->hasOne('notification', NotificationHistory::class)
            ->setCandidateKey('notification_history_id')
            ->setForeignKey('id')
            ->setJoinType('LEFT');
        $relations->hasOne('acknowledgement', AcknowledgementHistory::class)
            ->setCandidateKey('acknowledgement_history_id')
            ->setForeignKey('id')
            ->setJoinType('LEFT');
        $relations->hasOne('state', StateHistory::class)
            ->setCandidateKey('state_history_id')
            ->setForeignKey('id')
            ->setJoinType('LEFT');
    }
}
