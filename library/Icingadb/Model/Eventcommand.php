<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Eventcommand extends Model
{
    public function getTableName()
    {
        return 'eventcommand';
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

    public function getMetaData()
    {
        return [
            'zone_id'               => t('Eventcommand Zone Id'),
            'environment_id'        => t('Eventcommand Environment id'),
            'name_checksum'         => t('Eventcommand Name Checksum'),
            'properties_checksum'   => t('Eventcommand Properties Checksum'),
            'name'                  => t('Eventcommand Name'),
            'name_ci'               => t('Eventcommand Name (CI)'),
            'command'               => t('Eventcommand'),
            'timeout'               => t('Eventcommand Timeout')
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
            ->through(EventcommandCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->through(EventcommandCustomvar::class);
        $relations->belongsToMany('vars', CustomvarFlat::class)
            ->through(EventcommandCustomvar::class);

        $relations->hasMany('argument', EventcommandArgument::class);
        $relations->hasMany('envvar', EventcommandEnvvar::class);
        $relations->hasMany('host', Host::class);
        $relations->hasMany('service', Service::class);
    }
}
