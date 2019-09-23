<?php

namespace Icinga\Module\Eagle\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class Customvar extends Model
{
    public function getTableName()
    {
        return 'customvar';
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
            'name',
            'value'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);

        $relations->belongsToMany('checkcommand', Checkcommand::class)
            ->setThrough(CheckcommandCustomvar::class);
        $relations->belongsToMany('eventcommand', Eventcommand::class)
            ->setThrough(EventcommandCustomvar::class);
        $relations->belongsToMany('host', Host::class)
            ->setThrough(HostCustomvar::class);
        $relations->belongsToMany('hostgroup', Hostgroup::class)
            ->setThrough(HostgroupCustomvar::class);
        $relations->belongsToMany('notification', Notification::class)
            ->setThrough(NotificationCustomvar::class);
        $relations->belongsToMany('notificationcommand', Notificationcommand::class)
            ->setThrough(NotificationcommandCustomvar::class);
        $relations->belongsToMany('service', Service::class)
            ->setThrough(ServiceCustomvar::class);
        $relations->belongsToMany('servicegroup', Servicegroup::class)
            ->setThrough(ServicegroupCustomvar::class);
        $relations->belongsToMany('timeperiod', Timeperiod::class)
            ->setThrough(TimeperiodCustomvar::class);
        $relations->belongsToMany('user', User::class)
            ->setThrough(UserCustomvar::class);
        $relations->belongsToMany('usergroup', Usergroup::class)
            ->setThrough(UsergroupCustomvar::class);

        $relations->hasMany('customvar_flat', CustomvarFlat::class);
    }
}
