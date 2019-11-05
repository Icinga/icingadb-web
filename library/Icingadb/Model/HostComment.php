<?php

namespace Icinga\Module\Eagle\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class HostComment extends Model
{
    public function getTableName()
    {
        return 'host_comment';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'environment_id',
            'host_id',
            'name_checksum',
            'properties_checksum',
            'name',
            'author',
            'text',
            'entry_type',
            'entry_time',
            'is_persistent',
            'expire_time',
            'zone_id'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('host', Host::class);
        $relations->belongsTo('state', HostState::class)
            ->setForeignKey('acknowledgement_comment_id');
        $relations->belongsTo('zone', Zone::class);
    }
}
