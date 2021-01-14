<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Servicegroup extends Model
{
    public function getTableName()
    {
        return 'servicegroup';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'environment_id',
            'name_checksum',
            'properties_checksum',
            'name',
            'name_ci',
            'display_name',
            'zone_id'
        ];
    }

    public function getMetaData()
    {
        return [
            'environment_id'        => t('Servicegroup Environment Id'),
            'name_checksum'         => t('Servicegroup Name Checksum'),
            'properties_checksum'   => t('Servicegroup Properties Checksum'),
            'name'                  => t('Servicegroup Name'),
            'name_ci'               => t('Servicegroup Name (CI)'),
            'display_name'          => t('Servicegroup Display Name'),
            'zone_id'               => t('Servicegroup Zone Id')
        ];
    }

    public function getSearchColumns()
    {
        return ['name_ci'];
    }

    public function getDefaultSort()
    {
        return 'display_name';
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new ReRoute([
            'host'      => 'service.host',
            'hostgroup' => 'service.hostgroup'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('zone', Zone::class);

        $relations->belongsToMany('customvar', Customvar::class)
            ->through(ServicegroupCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->through(ServicegroupCustomvar::class);
        $relations->belongsToMany('vars', CustomvarFlat::class)
            ->through(ServicegroupCustomvar::class);
        $relations->belongsToMany('service', Service::class)
            ->through(ServicegroupMember::class);
    }
}
