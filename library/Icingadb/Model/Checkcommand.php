<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Common\Model;
use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;

/**
 * @property string $id
 * @property ?string $zone_id
 * @property string $environment_id
 * @property string $name_checksum
 * @property string $properties_checksum
 * @property string $name
 * @property string $name_ci
 * @property string $command
 * @property int $timeout
 */
class Checkcommand extends Model
{
    public function getTableName()
    {
        return 'checkcommand';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'zone_id',
            'environment_id',
            'name_checksum',
            'properties_checksum',
            'name',
            'name_ci',
            'command',
            'timeout'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'zone_id'               => t('Zone Id'),
            'environment_id'        => t('Environment Id'),
            'name_checksum'         => t('Checkcommand Name Checksum'),
            'properties_checksum'   => t('Checkcommand Properties Checksum'),
            'name'                  => t('Checkcommand Name'),
            'name_ci'               => t('Checkcommand Name (CI)'),
            'command'               => t('Checkcommand'),
            'timeout'               => t('Checkcommand Timeout')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new ReRoute([
            'hostgroup'     => 'host.hostgroup',
            'servicegroup'  => 'service.servicegroup'
        ]));

        $behaviors->add(new Binary([
            'id',
            'zone_id',
            'environment_id',
            'name_checksum',
            'properties_checksum'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('zone', Zone::class);

        $relations->belongsToMany('customvar', Customvar::class)
            ->through(CheckcommandCustomvar::class)
            ->setThroughAlias('t_checkcommand_customvar');
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->through(CheckcommandCustomvar::class);
        $relations->belongsToMany('vars', Vars::class)
            ->through(CheckcommandCustomvar::class);

        $relations->hasMany('argument', CheckcommandArgument::class);
        $relations->hasMany('envvar', CheckcommandEnvvar::class);
        $relations->hasMany('host', Host::class);
        $relations->hasMany('service', Service::class);
    }
}
