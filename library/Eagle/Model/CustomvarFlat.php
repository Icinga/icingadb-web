<?php

namespace Icinga\Module\Eagle\Model;

use Icinga\Module\Eagle\Model\Behavior\FlattenedObjectVars;
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
            ->setThrough(CheckcommandCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('eventcommand', Eventcommand::class)
            ->setThrough(EventcommandCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('host', Host::class)
            ->setThrough(HostCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('hostgroup', Hostgroup::class)
            ->setThrough(HostgroupCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('notification', Notification::class)
            ->setThrough(NotificationCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('notificationcommand', Notificationcommand::class)
            ->setThrough(NotificationcommandCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('service', Service::class)
            ->setThrough(ServiceCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('servicegroup', Servicegroup::class)
            ->setThrough(ServicegroupCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('timeperiod', Timeperiod::class)
            ->setThrough(TimeperiodCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('user', User::class)
            ->setThrough(UserCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('usergroup', Usergroup::class)
            ->setThrough(UsergroupCustomvar::class)
            ->setCandidateKey('customvar_id');
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new FlattenedObjectVars());
    }
}
