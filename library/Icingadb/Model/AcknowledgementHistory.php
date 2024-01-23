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
 * Model for table `acknowledgement_history`
 *
 * Please note that using this model will fetch history entries for decommissioned services. To avoid this, the query
 * needs a `acknowledgement_history.service_id IS NULL OR acknowledgement_history_service.id IS NOT NULL` where.
 *
 * @property string $id
 * @property string $environment_id
 * @property ?string $endpoint_id
 * @property string $object_type
 * @property string $host_id
 * @property ?string $service_id
 * @property DateTime $set_time
 * @property ?DateTime $clear_time
 * @property ?string $author
 * @property ?string $cleared_by
 * @property ?int $comment
 * @property ?DateTime $expire_time
 * @property ?bool $is_sticky
 * @property ?bool $is_persistent
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
            'environment_id'    => t('Environment Id'),
            'endpoint_id'       => t('Endpoint Id'),
            'object_type'       => t('Object Type'),
            'host_id'           => t('Host Id'),
            'service_id'        => t('Service Id'),
            'set_time'          => t('Acknowledgement Set Time'),
            'clear_time'        => t('Acknowledgement Clear Time'),
            'author'            => t('Acknowledgement Author'),
            'cleared_by'        => t('Acknowledgement Cleared By'),
            'comment'           => t('Acknowledgement Comment'),
            'expire_time'       => t('Acknowledgement Expire Time'),
            'is_sticky'         => t('Acknowledgement Is Sticky'),
            'is_persistent'     => t('Acknowledgement Is Persistent')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new BoolCast([
            'is_sticky',
            'is_persistent'
        ]));

        $behaviors->add(new MillisecondTimestamp([
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
