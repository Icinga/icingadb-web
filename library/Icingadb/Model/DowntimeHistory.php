<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use DateTime;
use Icinga\Module\Icingadb\Model\Behavior\BoolCast;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

/**
 * Model for table `downtime_history`
 *
 * Please note that using this model will fetch history entries for decommissioned services. To avoid this,
 * the query needs a `downtime_history.service_id IS NULL OR downtime_history_service.id IS NOT NULL` where.
 *
 * @property string $id
 * @property string $environment_id
 * @property ?string $endpoint_id
 * @property ?string $triggered_by_id
 * @property ?string $parent_id
 * @property string $object_type
 * @property string $host_id
 * @property ?string $service_id
 * @property DateTime $entry_time
 * @property string $author
 * @property ?string $cancelled_by
 * @property string $comment
 * @property bool $is_flexible
 * @property int $flexible_duration
 * @property DateTime $scheduled_start_time
 * @property DateTime $scheduled_end_time
 * @property DateTime $start_time
 * @property DateTime $end_time
 * @property string $has_been_cancelled
 * @property DateTime $trigger_time
 * @property ?DateTime $cancel_time
 */
class DowntimeHistory extends Model
{
    public function getTableName()
    {
        return 'downtime_history';
    }

    public function getKeyName()
    {
        return 'downtime_id';
    }

    public function getColumns()
    {
        return [
            'environment_id',
            'endpoint_id',
            'triggered_by_id',
            'parent_id',
            'object_type',
            'host_id',
            'service_id',
            'entry_time',
            'author',
            'cancelled_by',
            'comment',
            'is_flexible',
            'flexible_duration',
            'scheduled_start_time',
            'scheduled_end_time',
            'start_time',
            'end_time',
            'has_been_cancelled',
            'trigger_time',
            'cancel_time'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'environment_id'        => t('Environment Id'),
            'endpoint_id'           => t('Endpoint Id'),
            'triggered_by_id'       => t('Triggered By Downtime Id'),
            'parent_id'             => t('Parent Downtime Id'),
            'object_type'           => t('Object Type'),
            'host_id'               => t('Host Id'),
            'service_id'            => t('Service Id'),
            'entry_time'            => t('Downtime Entry Time'),
            'author'                => t('Downtime Author'),
            'cancelled_by'          => t('Downtime Cancelled By'),
            'comment'               => t('Downtime Comment'),
            'is_flexible'           => t('Downtime Is Flexible'),
            'flexible_duration'     => t('Downtime Flexible Duration'),
            'scheduled_start_time'  => t('Downtime Scheduled Start'),
            'scheduled_end_time'    => t('Downtime Scheduled End'),
            'start_time'            => t('Downtime Actual Start'),
            'end_time'              => t('Downtime Actual End'),
            'has_been_cancelled'    => t('Downtime Has Been Cancelled'),
            'trigger_time'          => t('Downtime Trigger Time'),
            'cancel_time'           => t('Downtime Cancel Time')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new BoolCast([
            'is_flexible',
            'has_been_cancelled'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'entry_time',
            'scheduled_start_time',
            'scheduled_end_time',
            'start_time',
            'end_time',
            'trigger_time',
            'cancel_time'
        ]));

        $behaviors->add(new Binary([
            'downtime_id',
            'environment_id',
            'endpoint_id',
            'triggered_by_id',
            'parent_id',
            'host_id',
            'service_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('triggered_by', self::class)
            ->setCandidateKey('triggered_by_id')
            ->setJoinType('LEFT');
        $relations->belongsTo('parent', self::class)
            ->setCandidateKey('parent_id')
            ->setJoinType('LEFT');
        $relations->belongsTo('endpoint', Endpoint::class);
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('history', History::class)
            ->setCandidateKey('downtime_id')
            ->setForeignKey('downtime_history_id');
        $relations->belongsTo('host', Host::class)->setJoinType('LEFT');
        $relations->belongsTo('service', Service::class)->setJoinType('LEFT');
    }
}
