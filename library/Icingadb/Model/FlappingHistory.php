<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\Binary;
use Icinga\Module\Icingadb\Model\Behavior\BoolCast;
use Icinga\Module\Icingadb\Model\Behavior\Timestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

/**
 * Model for table `flapping_history`
 *
 * Please note that using this model will fetch history entries for decommissioned services. To avoid this,
 * the query needs a `flapping_history.service_id IS NULL OR flapping_history_service.id IS NOT NULL` where.
 */
class FlappingHistory extends Model
{
    public function getTableName()
    {
        return 'flapping_history';
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
            'start_time',
            'end_time',
            'percent_state_change_start',
            'percent_state_change_end',
            'flapping_threshold_low',
            'flapping_threshold_high'
        ];
    }

    public function getMetaData()
    {
        return [
            'environment_id'                => t('Flapping Environment Id (History)'),
            'endpoint_id'                   => t('Flapping Endpoint Id (History)'),
            'object_type'                   => t('Flapping Object Type (History)'),
            'host_id'                       => t('Flapping Host Id (History)'),
            'service_id'                    => t('Flapping Service Id (History)'),
            'start_time'                    => t('Flapping Start Time (History)'),
            'end_time'                      => t('Flapping End Time (History)'),
            'percent_state_change_start'    => t('Flapping Percent State Change Start (History)'),
            'percent_state_change_end'      => t('Flapping Percent State Change End (History)'),
            'flapping_threshold_low'        => t('Flapping Threshold Low (History)'),
            'flapping_threshold_high'       => t('Flapping Threshold High (History)')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Timestamp([
            'start_time',
            'end_time'
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
            ->setForeignKey('flapping_history_id');
        $relations->belongsTo('host', Host::class);
        $relations->belongsTo('service', Service::class)->setJoinType('LEFT');
    }
}
