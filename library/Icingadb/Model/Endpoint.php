<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

/**
 * @property string $id
 * @property string $environment_id
 * @property string $name_checksum
 * @property string $properties_checksum
 * @property string $name
 * @property string $name_ci
 * @property string $zone_id
 */
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

    public function getColumnDefinitions()
    {
        return [
            'environment_id'        => t('Environment Id'),
            'name_checksum'         => t('Endpoint Name Checksum'),
            'properties_checksum'   => t('Endpoint Properties Checksum'),
            'name'                  => t('Endpoint Name'),
            'name_ci'               => t('Endpoint Name (CI)'),
            'zone_id'               => t('Zone Id')
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
