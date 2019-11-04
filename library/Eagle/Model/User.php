<?php

namespace Icinga\Module\Eagle\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class User extends Model
{
    public function getTableName()
    {
        return 'user';
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
            'groups_checksum',
            'name',
            'name_ci',
            'display_name',
            'email',
            'pager',
            'notifications_enabled',
            'timeperiod_id',
            'states',
            'types',
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
        $relations->belongsTo('timeperiod', Timeperiod::class);
        $relations->belongsTo('zone', Zone::class);

        $relations->belongsToMany('customvar', Customvar::class)
            ->setThrough(UserCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->setThrough(UserCustomvar::class);
        $relations->belongsToMany('notification', Notification::class)
            ->setThrough(NotificationUser::class);
        $relations->belongsToMany('usergroup', Usergroup::class)
            ->setThrough(UsergroupMember::class);
    }
}
