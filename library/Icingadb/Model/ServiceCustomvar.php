<?php

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class ServiceCustomvar extends Model
{
    public function getTableName()
    {
        return 'service_customvar';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'service_id',
            'customvar_id',
            'environment_id'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('service', Service::class);
        $relations->belongsTo('customvar', Customvar::class);
        $relations->belongsTo('customvar_flat', CustomvarFlat::class)
            ->setCandidateKey('customvar_id');
    }
}
