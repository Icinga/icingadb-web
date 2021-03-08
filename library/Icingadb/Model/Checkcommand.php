<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

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

    public function getMetaData()
    {
        return [
            'zone_id'               => t('Checkcommand Zone Id'),
            'environment_id'        => t('Checkcommand Environment Id'),
            'name_checksum'         => t('Checkcommand Name Checksum'),
            'properties_checksum'   => t('Checkcommand Properties Checksum'),
            'name'                  => t('Checkcommand Name'),
            'name_ci'               => t('Checkcommand Name (CI)'),
            'command'               => t('Checkcommand'),
            'timeout'               => t('Checkcommand Timeout')
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('zone', Zone::class);

        $relations->belongsToMany('customvar', Customvar::class)
            ->through(CheckcommandCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->through(CheckcommandCustomvar::class);
        $relations->belongsToMany('vars', CustomvarFlat::class)
            ->through(CheckcommandCustomvar::class);

        $relations->hasMany('argument', CheckcommandArgument::class)
            ->setForeignKey('command_id');
        $relations->hasMany('envvar', CheckcommandEnvvar::class)
            ->setForeignKey('command_id');
        $relations->hasMany('host', Host::class);
        $relations->hasMany('service', Service::class);
    }
}
