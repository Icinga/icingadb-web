<?php

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\FlattenedObjectVars;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class CustomvarFlat extends Model
{
    public function getTableName()
    {
        return 'customvar_flat';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'environment_id',
            'customvar_id',
            'flatname_checksum',
            'flatname',
            'flatvalue'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('customvar', Customvar::class);

        $relations->belongsToMany('checkcommand', Checkcommand::class)
            ->through(CheckcommandCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('eventcommand', Eventcommand::class)
            ->through(EventcommandCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('host', Host::class)
            ->through(HostCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('hostgroup', Hostgroup::class)
            ->through(HostgroupCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('notification', Notification::class)
            ->through(NotificationCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('notificationcommand', Notificationcommand::class)
            ->through(NotificationcommandCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('service', Service::class)
            ->through(ServiceCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('servicegroup', Servicegroup::class)
            ->through(ServicegroupCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('timeperiod', Timeperiod::class)
            ->through(TimeperiodCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('user', User::class)
            ->through(UserCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('usergroup', Usergroup::class)
            ->through(UsergroupCustomvar::class)
            ->setCandidateKey('customvar_id');
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new FlattenedObjectVars());
    }
}
