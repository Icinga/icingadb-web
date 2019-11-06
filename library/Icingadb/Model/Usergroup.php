<?php

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class Usergroup extends Model
{
    public function getTableName()
    {
        return 'usergroup';
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
            'customvars_checksum',
            'name',
            'name_ci',
            'display_name',
            'zone_id'
        ];
    }

    public function getSortRules()
    {
        return ['display_name'];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('zone', Zone::class);

        $relations->belongsToMany('customvar', Customvar::class)
            ->setThrough(UsergroupCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->setThrough(UsergroupCustomvar::class);
        $relations->belongsToMany('user', User::class)
            ->setThrough(UsergroupMember::class);
        $relations->belongsToMany('notification', Notification::class)
            ->setThrough(NotificationUsergroup::class);
    }
}
