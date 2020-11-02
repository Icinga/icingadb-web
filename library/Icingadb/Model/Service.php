<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

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

    public function getMetaData()
    {
        return [
            'environment_id'            => t('Service Environment Id'),
            'name_checksum'             => t('Service Name Checksum'),
            'properties_checksum'       => t('Service Properties Checksum'),
            'host_id'                   => t('Service Host Id'),
            'name'                      => t('Service Name'),
            'name_ci'                   => t('Service Name (CI)'),
            'display_name'              => t('Service Display Name'),
            'checkcommand'              => t('Service Checkcommand'),
            'checkcommand_id'           => t('Service Checkcommand Id'),
            'max_check_attempts'        => t('Service Max Check Attempts'),
            'check_timeperiod'          => t('Service Check Timeperiod'),
            'check_timeperiod_id'       => t('Service Check Timeperiod Id'),
            'check_timeout'             => t('Service Check Timeout'),
            'check_interval'            => t('Service Check Interval'),
            'check_retry_interval'      => t('Service Check Retry Inverval'),
            'active_checks_enabled'     => t('Service Active Checks Enabled'),
            'passive_checks_enabled'    => t('Service Passive Checks Enabled'),
            'event_handler_enabled'     => t('Service Event Handler Enabled'),
            'notifications_enabled'     => t('Service Notifications Enabled'),
            'flapping_enabled'          => t('Service Flapping Enabled'),
            'flapping_threshold_low'    => t('Service Flapping Threshold Low'),
            'flapping_threshold_high'   => t('Service Flapping Threshold High'),
            'perfdata_enabled'          => t('Service Performance Data Enabled'),
            'eventcommand'              => t('Service Eventcommand'),
            'eventcommand_id'           => t('Service Eventcommand Id'),
            'is_volatile'               => t('Service Is Volatile'),
            'action_url_id'             => t('Service Action Url Id'),
            'notes_url_id'              => t('Service Notes Url Id'),
            'notes'                     => t('Service Notes'),
            'icon_image_id'             => t('Service Icon Image Id'),
            'icon_image_alt'            => t('Service Icon Image Alt'),
            'zone'                      => t('Service Zone'),
            'zone_id'                   => t('Service Zone Id'),
            'command_endpoint'          => t('Service Command Endpoint'),
            'command_endpoint_id'       => t('Service Command Endpoint Id')
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
        $relations->hasMany('downtime', Downtime::class)->setJoinType('LEFT');
        $relations->hasMany('history', History::class);
        $relations->hasMany('notification', Notification::class)->setJoinType('LEFT');
        $relations->hasMany('notification_history', NotificationHistory::class);
    }
}
