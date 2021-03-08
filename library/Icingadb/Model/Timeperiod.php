<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class Timeperiod extends Model
{
    public function getTableName()
    {
        return 'timeperiod';
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
            'prefer_includes',
            'zone_id'
        ];
    }

    public function getMetaData()
    {
        return [
            'environment_id'        => t('Timeperiod Environment Id'),
            'name_checksum'         => t('Timeperiod Name Checksum'),
            'properties_checksum'   => t('Timeperiod Properties Checksum'),
            'name'                  => t('Timeperiod Name'),
            'name_ci'               => t('Timeperiod Name (CI)'),
            'display_name'          => t('Timeperiod Display Name'),
            'prefer_includes'       => t('Timeperiod Prefer Includes'),
            'zone_id'               => t('Timeperiod Zone Id')
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('zone', Zone::class);

        $relations->belongsToMany('customvar', Customvar::class)
            ->through(TimeperiodCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->through(TimeperiodCustomvar::class);
        $relations->belongsToMany('vars', CustomvarFlat::class)
            ->through(TimeperiodCustomvar::class);

        // TODO: Decide how to establish the override relations

        $relations->hasMany('range', TimeperiodRange::class);
        $relations->hasMany('host', Host::class)
            ->setForeignKey('check_timeperiod_id');
        $relations->hasMany('Notification', Notification::class);
        $relations->hasMany('service', Service::class)
            ->setForeignKey('check_timeperiod_id');
        $relations->hasMany('user', User::class);
    }
}
