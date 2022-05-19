<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Endpoint extends Model
{
    public function getTableName()
    {
        return 'endpoint';
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
            'zone_id'
        ];
    }

    public function getMetaData()
    {
        return [
            'environment_id'        => t('Endpoint Environment Id'),
            'name_checksum'         => t('Endpoint Name Checksum'),
            'properties_checksum'   => t('Endpoint Properties Checksum'),
            'name'                  => t('Endpoint Name'),
            'name_ci'               => t('Endpoint Name (CI)'),
            'zone_id'               => t('Endpoint Zone Id')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id',
            'environment_id',
            'name_checksum',
            'properties_checksum',
            'zone_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('zone', Zone::class);

        $relations->hasMany('host', Host::class)
            ->setForeignKey('command_endpoint_id');
        $relations->hasMany('service', Service::class)
            ->setForeignKey('command_endpoint_id');
    }
}
