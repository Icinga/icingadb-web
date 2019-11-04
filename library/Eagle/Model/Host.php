<?php

namespace Icinga\Module\Eagle\Model;

use Icinga\Module\Eagle\Model\Behavior\BoolCast;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

/**
 * Host model.
 */
class Host extends Model
{
    public function getTableName()
    {
        return 'host';
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
            'address',
            'address',
            'address_bin',
            'address',
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

    public function getSortRules()
    {
        return ['display_name'];
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
        $relations->belongsTo('checkcommand', Checkcommand::class);
        $relations->belongsTo('timeperiod', Timeperiod::class)
            ->setCandidateKey('check_timeperiod_id');
        $relations->belongsTo('action_url', ActionUrl::class);
        $relations->belongsTo('notes_url', NotesUrl::class);
        $relations->belongsTo('icon_image', IconImage::class);
        $relations->belongsTo('zone', Zone::class);
        $relations->belongsTo('endpoint', Endpoint::class)
            ->setCandidateKey('command_endpoint_id');

        $relations->belongsToMany('customvar', Customvar::class)
            ->setThrough(HostCustomvar::class);
        $relations->belongsToMany('vars', CustomvarFlat::class)
            ->setThrough(HostCustomvar::class);
        $relations->belongsToMany('hostgroup', Hostgroup::class)
            ->setThrough(HostgroupMember::class);

        $relations->hasOne('state', HostState::class)->setJoinType('LEFT');
        $relations->hasMany('comment', Comment::class);
        $relations->hasMany('downtime', Downtime::class);
        $relations->hasMany('notification', Notification::class);
        $relations->hasMany('service', Service::class)->setJoinType('LEFT');
    }
}
