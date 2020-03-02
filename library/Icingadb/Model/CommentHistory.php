<?php

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\BoolCast;
use Icinga\Module\Icingadb\Model\Behavior\Timestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

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
            'comment',
            'entry_type',
            'is_persistent',
            'expire_time',
            'remove_time',
            'has_been_removed'
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new BoolCast([
            'is_persistent',
            'has_been_removed'
        ]));

        $behaviors->add(new Timestamp([
            'entry_time',
            'expire_time',
            'remove_time'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('endpoint', Endpoint::class);
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('host', Host::class);
        $relations->belongsTo('service', Service::class);
    }
}
