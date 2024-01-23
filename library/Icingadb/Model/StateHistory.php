<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use DateTime;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

/**
 * Model for table `state_history`
 *
 * Please note that using this model will fetch history entries for decommissioned services. To avoid this,
 * the query needs a `state_history.service_id IS NULL OR state_history_service.id IS NOT NULL` where.
 *
 * @property string $id
 * @property string $environment_id
 * @property ?string $endpoint_id
 * @property string $object_type
 * @property string $host_id
 * @property ?string $service_id
 * @property DateTime $event_time
 * @property string $state_type
 * @property int $soft_state
 * @property int $hard_state
 * @property int $check_attempt
 * @property int $previous_soft_state
 * @property int $previous_hard_state
 * @property ?string $output
 * @property ?string $long_output
 * @property int $max_check_attempts
 * @property ?string $check_source
 * @property ?string $scheduling_source
 */
class StateHistory extends Model
{
    public function getTableName()
    {
        return 'state_history';
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
            'state_type',
            'soft_state',
            'hard_state',
            'check_attempt',
            'previous_soft_state',
            'previous_hard_state',
            'output',
            'long_output',
            'max_check_attempts',
            'check_source',
            'scheduling_source'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'environment_id'        => t('Environment Id'),
            'endpoint_id'           => t('Endpoint Id'),
            'object_type'           => t('Object Type'),
            'host_id'               => t('Host Id'),
            'service_id'            => t('Service Id'),
            'event_time'            => t('Event Time'),
            'state_type'            => t('Event State Type'),
            'soft_state'            => t('Event Soft State'),
            'hard_state'            => t('Event Hard State'),
            'check_attempt'         => t('Event Check Attempt No.'),
            'previous_soft_state'   => t('Event Previous Soft State'),
            'previous_hard_state'   => t('Event Previous Hard State'),
            'output'                => t('Event Output'),
            'long_output'           => t('Event Long Output'),
            'max_check_attempts'    => t('Event Max Check Attempts'),
            'check_source'          => t('Event Check Source')
        ];
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
            'service_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('endpoint', Endpoint::class);
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('history', History::class)
            ->setCandidateKey('id')
            ->setForeignKey('state_history_id');
        $relations->belongsTo('host', Host::class);
        $relations->belongsTo('service', Service::class)->setJoinType('LEFT');
    }
}
