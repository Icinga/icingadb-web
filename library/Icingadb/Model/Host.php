<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\BoolCast;
use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
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
            'name',
            'name_ci',
            'display_name',
            'address',
            'address6',
            'address_bin',
            'address6_bin',
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
            'environment_id'            => t('Host Environment Id'),
            'name_checksum'             => t('Host Name Checksum'),
            'properties_checksum'       => t('Host Properties Checksum'),
            'name'                      => t('Host Name'),
            'name_ci'                   => t('Host Name (CI)'),
            'display_name'              => t('Host Display Name'),
            'address'                   => t('Host Address (IPv4)'),
            'address6'                  => t('Host Address (IPv6)'),
            'address_bin'               => t('Host Address (IPv4, Binary)'),
            'address6_bin'              => t('Host Address (IPv6, Binary)'),
            'checkcommand'              => t('Host Checkcommand'),
            'checkcommand_id'           => t('Host Checkcommand Id'),
            'max_check_attempts'        => t('Host Max Check Attempts'),
            'check_timeperiod'          => t('Host Check Timeperiod'),
            'check_timeperiod_id'       => t('Host Check Timeperiod Id'),
            'check_timeout'             => t('Host Check Timeout'),
            'check_interval'            => t('Host Check Interval'),
            'check_retry_interval'      => t('Host Check Retry Inverval'),
            'active_checks_enabled'     => t('Host Active Checks Enabled'),
            'passive_checks_enabled'    => t('Host Passive Checks Enabled'),
            'event_handler_enabled'     => t('Host Event Handler Enabled'),
            'notifications_enabled'     => t('Host Notifications Enabled'),
            'flapping_enabled'          => t('Host Flapping Enabled'),
            'flapping_threshold_low'    => t('Host Flapping Threshold Low'),
            'flapping_threshold_high'   => t('Host Flapping Threshold High'),
            'perfdata_enabled'          => t('Host Performance Data Enabled'),
            'eventcommand'              => t('Host Eventcommand'),
            'eventcommand_id'           => t('Host Eventcommand Id'),
            'is_volatile'               => t('Host Is Volatile'),
            'action_url_id'             => t('Host Action Url Id'),
            'notes_url_id'              => t('Host Notes Url Id'),
            'notes'                     => t('Host Notes'),
            'icon_image_id'             => t('Host Icon Image Id'),
            'icon_image_alt'            => t('Host Icon Image Alt'),
            'zone'                      => t('Host Zone'),
            'zone_id'                   => t('Host Zone Id'),
            'command_endpoint'          => t('Host Command Endpoint'),
            'command_endpoint_id'       => t('Host Command Endpoint Id')
        ];
    }

    public function getSearchColumns()
    {
        return ['name_ci'];
    }

    public function getDefaultSort()
    {
        return 'host.display_name';
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

        $behaviors->add(new ReRoute([
            'servicegroup'  => 'service.servicegroup'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('checkcommand', Checkcommand::class);
        $relations->belongsTo('timeperiod', Timeperiod::class)
            ->setCandidateKey('check_timeperiod_id');
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
            ->through(HostCustomvar::class);
        $relations->belongsToMany('vars', CustomvarFlat::class)
            ->through(HostCustomvar::class);
        $relations->belongsToMany('hostgroup', Hostgroup::class)
            ->through(HostgroupMember::class);

        $relations->hasOne('state', HostState::class)->setJoinType('LEFT');
        $relations->hasMany('comment', Comment::class)->setJoinType('LEFT');
        $relations->hasMany('downtime', Downtime::class)->setJoinType('LEFT');
        $relations->hasMany('history', History::class);
        $relations->hasMany('notification', Notification::class)->setJoinType('LEFT');
        $relations->hasMany('notification_history', NotificationHistory::class);
        $relations->hasMany('service', Service::class)->setJoinType('LEFT');
    }
}
