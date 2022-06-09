<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\BoolCast;
use Icinga\Module\Icingadb\Model\Behavior\Timestamp;
use ipl\Orm\Behavior\Binary;
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

    public function getColumnDefinitions()
    {
        return [
            'environment_id'    => t('Acknowledgement Environment Id (History)'),
            'endpoint_id'       => t('Acknowledgement Endpoint Id (History)'),
            'object_type'       => t('Acknowledgement Object Type (History)'),
            'host_id'           => t('Acknowledgement Host Id (History)'),
            'service_id'        => t('Acknowledgement Service Id (History)'),
            'set_time'          => t('Acknowledgement Set Time (History)'),
            'clear_time'        => t('Acknowledgement Clear Time (History)'),
            'author'            => t('Acknowledgement Author (History)'),
            'cleared_by'        => t('Acknowledgement Cleared By (History)'),
            'comment'           => t('Acknowledgement Comment (History)'),
            'expire_time'       => t('Acknowledgement Expire Time (History)'),
            'is_sticky'         => t('Acknowledgement Is Sticky (History)'),
            'is_persistent'     => t('Acknowledgement Is Persistent (History)')
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

        $behaviors->add(new Binary([
            'id',
            'environment_id',
            'endpoint_id',
            'host_id',
            'service_id',
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('endpoint', Endpoint::class);
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('history', History::class)
            ->setCandidateKey('id')
            ->setForeignKey('acknowledgement_history_id');
        $relations->belongsTo('host', Host::class);
        $relations->belongsTo('service', Service::class)->setJoinType('LEFT');
    }
}
