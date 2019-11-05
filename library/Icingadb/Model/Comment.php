<?php

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\BoolCast;
use Icinga\Module\Icingadb\Model\Behavior\Timestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Comment extends Model
{
    public function getTableName()
    {
        return 'comment';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'environment_id',
            'object_type',
            'host_id',
            'service_id',
            'name_checksum',
            'properties_checksum',
            'name',
            'author',
            'text',
            'entry_type',
            'entry_time',
            'is_persistent',
            'is_sticky',
            'expire_time',
            'zone_id'
        ];
    }

    public function getSortRules()
    {
        return ['entry_time DESC'];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new BoolCast([
            'is_persistent',
            'is_sticky'
        ]));

        $behaviors->add(new Timestamp([
            'entry_time',
            'expire_time'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('host', Host::class)->setJoinType('LEFT');
        $relations->belongsTo('service', Service::class)->setJoinType('LEFT');
        $relations->belongsTo('zone',Zone::class);
    }
}
