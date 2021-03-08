<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\Timestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

/**
 * Model for table `state_history`
 *
 * Please note that using this model will fetch history entries for decommissioned services. To avoid this,
 * the query needs a `state_history.service_id IS NULL OR state_history_service.id IS NOT NULL` where.
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
            'attempt',
            'previous_soft_state',
            'previous_hard_state',
            'output',
            'long_output',
            'max_check_attempts',
            'check_source'
        ];
    }

    public function getMetaData()
    {
        return [
            'environment_id'        => t('State Environment Id (History)'),
            'endpoint_id'           => t('State Endpoint Id (History)'),
            'object_type'           => t('State Object Type (History)'),
            'host_id'               => t('State Host Id (History)'),
            'service_id'            => t('State Service Id (History)'),
            'event_time'            => t('State Event Time (History)'),
            'state_type'            => t('State Type (History)'),
            'soft_state'            => t('Soft State (History)'),
            'hard_state'            => t('Hard State (History)'),
            'attempt'               => t('State Attempt No. (History)'),
            'previous_soft_state'   => t('Previous Soft State (History)'),
            'previous_hard_state'   => t('Previous Hard State (History)'),
            'output'                => t('State Output (History)'),
            'long_output'           => t('State Long Output (History)'),
            'max_check_attempts'    => t('State Max Check Attempts (History)'),
            'check_source'          => t('State Check Source (History)')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Timestamp([
            'event_time'
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
