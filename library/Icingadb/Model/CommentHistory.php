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
 * Model for table `comment_history`
 *
 * Please note that using this model will fetch history entries for decommissioned services. To avoid this,
 * the query needs a `comment_history.service_id IS NULL OR comment_history_service.id IS NOT NULL` where.
 */
class CommentHistory extends Model
{
    public function getTableName()
    {
        return 'comment_history';
    }

    public function getKeyName()
    {
        return 'comment_id';
    }

    public function getColumns()
    {
        return [
            'environment_id',
            'endpoint_id',
            'object_type',
            'host_id',
            'service_id',
            'entry_time',
            'author',
            'removed_by',
            'comment',
            'entry_type',
            'is_persistent',
            'is_sticky',
            'expire_time',
            'remove_time',
            'has_been_removed'
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
            'entry_time'        => t('Comment Entry Time'),
            'author'            => t('Comment Author'),
            'removed_by'        => t('Comment Removed By'),
            'comment'           => t('Comment Text'),
            'entry_type'        => t('Comment Entry Type'),
            'is_persistent'     => t('Comment Is Persistent'),
            'is_sticky'         => t('Comment Is Sticky'),
            'expire_time'       => t('Comment Expire Time'),
            'remove_time'       => t('Comment Remove Time'),
            'has_been_removed'  => t('Comment Has Been Removed')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new BoolCast([
            'is_persistent',
            'is_sticky',
            'has_been_removed'
        ]));

        $behaviors->add(new Timestamp([
            'entry_time',
            'expire_time',
            'remove_time'
        ]));

        $behaviors->add(new Binary([
            'comment_id',
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
            ->setCandidateKey('comment_id')
            ->setForeignKey('comment_history_id');
        $relations->belongsTo('host', Host::class);
        $relations->belongsTo('service', Service::class)->setJoinType('LEFT');
    }
}
