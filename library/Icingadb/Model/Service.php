<?php

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\BoolCast;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Service extends Model
{
    public function getTableName()
    {
        return 'service';
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
            'host_id',
            'name',
            'name_ci',
            'display_name',
            'checkcommand',
            'checkcommand_id',
            'max_check_attempts',
            'check_timeperiod',
            'check_timeperiod_id',
            'check_timeout',
            'check_interval',
            'check_retry_interval',
            'active_checks_enabled',
            'passive_checks_enabled',
            'event_handler_enabled',
            'notifications_enabled',
            'flapping_enabled',
            'flapping_threshold_low',
            'flapping_threshold_high',
            'perfdata_enabled',
            'eventcommand',
            'eventcommand_id',
            'is_volatile',
            'action_url_id',
            'notes_url_id',
            'notes',
            'icon_image_id',
            'icon_image_alt',
            'zone',
            'zone_id',
            'command_endpoint',
            'command_endpoint_id'
        ];
    }

    public function getSearchColumns()
    {
        return ['name_ci'];
    }

    public function getDefaultSort()
    {
        return 'service.display_name';
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new BoolCast([
            'active_checks_enabled',
            'passive_checks_enabled',
            'event_handler_enabled',
            'notifications_enabled',
            'flapping_enabled'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('host', Host::class)->setJoinType('LEFT');
        $relations->belongsTo('checkcommand', Checkcommand::class);
        $relations->belongsTo('timeperiod', Timeperiod::class)
            ->setCandidateKey('check_timeperiod_id');
        $relations->belongsTo('eventcommand', Eventcommand::class);
        $relations->belongsTo('action_url', ActionUrl::class)
            ->setCandidateKey('action_url_id')
            ->setForeignKey('id');
        $relations->belongsTo('notes_url', NotesUrl::class)
            ->setCandidateKey('notes_url_id')
            ->setForeignKey('id');
        $relations->belongsTo('icon_image', IconImage::class);
        $relations->belongsTo('zone', Zone::class);
        $relations->belongsTo('endpoint', Endpoint::class)
            ->setCandidateKey('command_endpoint_id');

        $relations->belongsToMany('customvar', Customvar::class)
            ->through(ServiceCustomvar::class);
        $relations->belongsToMany('vars', CustomvarFlat::class)
            ->through(ServiceCustomvar::class);
        $relations->belongsToMany('servicegroup', Servicegroup::class)
            ->through(ServicegroupMember::class);
        $relations->belongsToMany('hostgroup', Hostgroup::class)
            ->through(HostgroupMember::class);

        $relations->hasOne('state', ServiceState::class)->setJoinType('LEFT');
        $relations->hasMany('comment', Comment::class)->setJoinType('LEFT');
        $relations->hasMany('downtime', Downtime::class);
        $relations->hasMany('history', History::class);
        $relations->hasMany('notification', Notification::class);
        $relations->hasMany('notification_history', NotificationHistory::class);
    }
}
