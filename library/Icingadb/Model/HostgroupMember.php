<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class HostgroupMember extends Model
{
    public function getTableName()
    {
        return 'hostgroup_member';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'host_id',
            'hostgroup_id',
            'environment_id'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('hostgroup', Hostgroup::class);
        $relations->belongsTo('host', Host::class);

        $relations->hasMany('service', Service::class)
            ->setForeignKey('host_id')
            ->setCandidateKey('host_id');
    }
}
