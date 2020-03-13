<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class ActionUrl extends Model
{
    public function getTableName()
    {
        return 'action_url';
    }

    public function getKeyName()
    {
        return ['environment_id', 'id'];
    }

    public function getColumns()
    {
        return [
            'action_url',
            'environment_id'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);

        $relations->hasMany('host', Host::class)
            ->setCandidateKey('id')
            ->setForeignKey('action_url_id');
        $relations->hasMany('service', Service::class)
            ->setCandidateKey('id')
            ->setForeignKey('action_url_id');
    }
}
