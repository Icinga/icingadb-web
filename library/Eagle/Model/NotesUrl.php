<?php

namespace Icinga\Module\Eagle\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class NotesUrl extends Model
{
    public function getTableName()
    {
        return 'notes_url';
    }

    public function getKeyName()
    {
        return ['environment_id', 'id'];
    }

    public function getColumns()
    {
        return [
            'notes_url',
            'env_id'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);

        $relations->hasMany('host', Host::class);
        $relations->hasMany('service', Service::class);
    }
}
