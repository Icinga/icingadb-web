<?php

namespace Icinga\Module\Icingadb\Model;

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
            'environment_id'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);

        $relations->hasMany('host', Host::class)
            ->setCandidateKey('id')
            ->setForeignKey('notes_url_id');
        $relations->hasMany('service', Service::class);
    }
}
