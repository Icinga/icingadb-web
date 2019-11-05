<?php

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class ServicegroupMember extends Model
{
    public function getTableName()
    {
        return 'servicegroup_member';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'service_id',
            'servicegroup_id',
            'environment_id'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('servicegroup', Servicegroup::class);
        $relations->belongsTo('service', Service::class);
    }
}
