<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

/**
 * @property string $id
 * @property string $service_id
 * @property string $servicegroup_id
 * @property string $environment_id
 */
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

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id',
            'service_id',
            'servicegroup_id',
            'environment_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('servicegroup', Servicegroup::class);
        $relations->belongsTo('service', Service::class);
    }
}
