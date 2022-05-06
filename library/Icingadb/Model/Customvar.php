<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

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
            ->through(CheckcommandCustomvar::class);
        $relations->belongsToMany('eventcommand', Eventcommand::class)
            ->through(EventcommandCustomvar::class);
        $relations->belongsToMany('host', Host::class)
            ->through(HostCustomvar::class);
        $relations->belongsToMany('hostgroup', Hostgroup::class)
            ->through(HostgroupCustomvar::class);
        $relations->belongsToMany('notification', Notification::class)
            ->through(NotificationCustomvar::class);
        $relations->belongsToMany('notificationcommand', Notificationcommand::class)
            ->through(NotificationcommandCustomvar::class);
        $relations->belongsToMany('service', Service::class)
            ->through(ServiceCustomvar::class);
        $relations->belongsToMany('servicegroup', Servicegroup::class)
            ->through(ServicegroupCustomvar::class);
        $relations->belongsToMany('timeperiod', Timeperiod::class)
            ->through(TimeperiodCustomvar::class);
        $relations->belongsToMany('user', User::class)
            ->through(UserCustomvar::class);
        $relations->belongsToMany('usergroup', Usergroup::class)
            ->through(UsergroupCustomvar::class);

        $relations->hasMany('customvar_flat', CustomvarFlat::class);
    }
}
