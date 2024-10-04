<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Model\Behavior\BoolCast;
use Icinga\Module\Icingadb\Model\Behavior\HasProblematicParent;
use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Defaults;
use ipl\Orm\Model;
use ipl\Orm\Relations;
use ipl\Orm\ResultSet;
use ipl\Sql\Expression;

/**
 * @property string $id
 * @property string $environment_id
 * @property string $name_checksum
 * @property string $properties_checksum
 * @property string $host_id
 * @property string $name
 * @property string $name_ci
 * @property string $display_name
 * @property string $checkcommand_name
 * @property string $checkcommand_id
 * @property int $max_check_attempts
 * @property string $check_timeperiod_name
 * @property ?string $check_timeperiod_id
 * @property ?int $check_timeout
 * @property int $check_interval
 * @property int $check_retry_interval
 * @property bool $active_checks_enabled
 * @property bool $passive_checks_enabled
 * @property bool $event_handler_enabled
 * @property bool $notifications_enabled
 * @property bool $flapping_enabled
 * @property float $flapping_threshold_low
 * @property float $flapping_threshold_high
 * @property bool $perfdata_enabled
 * @property string $eventcommand_name
 * @property ?string $eventcommand_id
 * @property bool $is_volatile
 * @property ?string $action_url_id
 * @property ?string $notes_url_id
 * @property string $notes
 * @property ?string $icon_image_id
 * @property string $icon_image_alt
 * @property string $zone_name
 * @property ?string $zone_id
 * @property string $command_endpoint_name
 * @property ?string $command_endpoint_id
 * @property ?int $affected_children
 */
class Service extends Model
{
    use Auth;

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
            'checkcommand_name',
            'checkcommand_id',
            'max_check_attempts',
            'check_timeperiod_name',
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
            'eventcommand_name',
            'eventcommand_id',
            'is_volatile',
            'action_url_id',
            'notes_url_id',
            'notes',
            'icon_image_id',
            'icon_image_alt',
            'zone_name',
            'zone_id',
            'command_endpoint_name',
            'command_endpoint_id',
            'affected_children' => new Expression('10')
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'environment_id'            => t('Environment Id'),
            'name_checksum'             => t('Service Name Checksum'),
            'properties_checksum'       => t('Service Properties Checksum'),
            'host_id'                   => t('Host Id'),
            'name'                      => t('Service Name'),
            'name_ci'                   => t('Service Name (CI)'),
            'display_name'              => t('Service Display Name'),
            'checkcommand_name'         => t('Checkcommand Name'),
            'checkcommand_id'           => t('Checkcommand Id'),
            'max_check_attempts'        => t('Service Max Check Attempts'),
            'check_timeperiod_name'     => t('Check Timeperiod Name'),
            'check_timeperiod_id'       => t('Check Timeperiod Id'),
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
            'eventcommand_name'         => t('Eventcommand Name'),
            'eventcommand_id'           => t('Eventcommand Id'),
            'is_volatile'               => t('Service Is Volatile'),
            'action_url_id'             => t('Action Url Id'),
            'notes_url_id'              => t('Notes Url Id'),
            'notes'                     => t('Service Notes'),
            'icon_image_id'             => t('Icon Image Id'),
            'icon_image_alt'            => t('Icon Image Alt'),
            'zone_name'                 => t('Zone Name'),
            'zone_id'                   => t('Zone Id'),
            'command_endpoint_name'     => t('Endpoint Name'),
            'command_endpoint_id'       => t('Endpoint Id'),
            'affected_children'         => t('Affected Children')
        ];
    }

    public function getSearchColumns()
    {
        return ['name_ci', 'display_name'];
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
            'flapping_enabled',
            'is_volatile'
        ]));

        $behaviors->add(new ReRoute([
            'child'         => 'to.from',
            'parent'        => 'from.to',
            'user'          => 'notification.user',
            'usergroup'     => 'notification.usergroup'
        ]));

        $behaviors->add(new Binary([
            'id',
            'environment_id',
            'name_checksum',
            'properties_checksum',
            'host_id',
            'checkcommand_id',
            'check_timeperiod_id',
            'eventcommand_id',
            'action_url_id',
            'notes_url_id',
            'icon_image_id',
            'zone_id',
            'command_endpoint_id'
        ]));

        $behaviors->add(new HasProblematicParent());
    }

    public function createDefaults(Defaults $defaults)
    {
        $defaults->add('vars', function (self $subject) {
            if (! $subject->customvar_flat instanceof ResultSet) {
                $this->applyRestrictions($subject->customvar_flat);
            }

            $vars = [];
            foreach ($subject->customvar_flat as $customVar) {
                $vars[$customVar->flatname] = $customVar->flatvalue;
            }

            return $vars;
        });

        $defaults->add('customvars', function (self $subject) {
            if (! $subject->customvar instanceof ResultSet) {
                $this->applyRestrictions($subject->customvar);
            }

            $vars = [];
            foreach ($subject->customvar as $customVar) {
                $vars[$customVar->name] = json_decode($customVar->value, true);
            }

            return $vars;
        });
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('host', Host::class)->setJoinType('LEFT');
        $relations->belongsTo('checkcommand', Checkcommand::class);
        $relations->belongsTo('timeperiod', Timeperiod::class)
            ->setCandidateKey('check_timeperiod_id')
            ->setJoinType('LEFT');
        $relations->belongsTo('eventcommand', Eventcommand::class);
        $relations->belongsTo('action_url', ActionUrl::class)
            ->setCandidateKey('action_url_id')
            ->setForeignKey('id');
        $relations->belongsTo('notes_url', NotesUrl::class)
            ->setCandidateKey('notes_url_id')
            ->setForeignKey('id');
        $relations->belongsTo('icon_image', IconImage::class)
            ->setCandidateKey('icon_image_id')
            ->setJoinType('LEFT');
        $relations->belongsTo('zone', Zone::class);
        $relations->belongsTo('endpoint', Endpoint::class)
            ->setCandidateKey('command_endpoint_id');

        $relations->belongsToMany('customvar', Customvar::class)
            ->through(ServiceCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->through(ServiceCustomvar::class);
        $relations->belongsToMany('vars', Vars::class)
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

        $relations->belongsToMany('from', DependencyEdge::class)
            ->setTargetCandidateKey('from_node_id')
            ->setTargetForeignKey('id')
            ->through(DependencyNode::class);
        $relations->belongsToMany('to', DependencyEdge::class)
            ->setTargetCandidateKey('to_node_id')
            ->setTargetForeignKey('id')
            ->through(DependencyNode::class);
    }
}
