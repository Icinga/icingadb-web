<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Backend;
use ipl\Orm\Behavior\BoolCast;
use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Defaults;
use ipl\Orm\Model;
use ipl\Orm\Relations;
use ipl\Orm\ResultSet;

/**
 * Host model.
 *
 * @property string $id
 * @property string $environment_id
 * @property string $name_checksum
 * @property string $properties_checksum
 * @property string $name
 * @property string $name_ci
 * @property string $display_name
 * @property string $address
 * @property string $address6
 * @property ?string $address_bin
 * @property ?string $address6_bin
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
 * @property ?int $total_children
 */
class Host extends Model
{
    use Auth;

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
        $columns = [
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
            'command_endpoint_id'
        ];

        if (Backend::supportsDependencies()) {
            $columns[] = 'total_children';
        }

        return $columns;
    }

    public function getColumnDefinitions()
    {
        $columns = [
            'environment_id'            => t('Environment Id'),
            'name_checksum'             => t('Host Name Checksum'),
            'properties_checksum'       => t('Host Properties Checksum'),
            'name'                      => t('Host Name'),
            'name_ci'                   => t('Host Name (CI)'),
            'display_name'              => t('Host Display Name'),
            'address'                   => t('Host Address (IPv4)'),
            'address6'                  => t('Host Address (IPv6)'),
            'address_bin'               => t('Host Address (IPv4, Binary)'),
            'address6_bin'              => t('Host Address (IPv6, Binary)'),
            'checkcommand_name'         => t('Checkcommand Name'),
            'checkcommand_id'           => t('Checkcommand Id'),
            'max_check_attempts'        => t('Host Max Check Attempts'),
            'check_timeperiod_name'     => t('Check Timeperiod Name'),
            'check_timeperiod_id'       => t('Check Timeperiod Id'),
            'check_timeout'             => t('Host Check Timeout'),
            'check_interval'            => t('Host Check Interval'),
            'check_retry_interval'      => t('Host Check Retry Interval'),
            'active_checks_enabled'     => t('Host Active Checks Enabled'),
            'passive_checks_enabled'    => t('Host Passive Checks Enabled'),
            'event_handler_enabled'     => t('Host Event Handler Enabled'),
            'notifications_enabled'     => t('Host Notifications Enabled'),
            'flapping_enabled'          => t('Host Flapping Enabled'),
            'flapping_threshold_low'    => t('Host Flapping Threshold Low'),
            'flapping_threshold_high'   => t('Host Flapping Threshold High'),
            'perfdata_enabled'          => t('Host Performance Data Enabled'),
            'eventcommand_name'         => t('Eventcommand Name'),
            'eventcommand_id'           => t('Eventcommand Id'),
            'is_volatile'               => t('Host Is Volatile'),
            'action_url_id'             => t('Action Url Id'),
            'notes_url_id'              => t('Notes Url Id'),
            'notes'                     => t('Host Notes'),
            'icon_image_id'             => t('Icon Image Id'),
            'icon_image_alt'            => t('Icon Image Alt'),
            'zone_name'                 => t('Zone Name'),
            'zone_id'                   => t('Zone Id'),
            'command_endpoint_name'     => t('Endpoint Name'),
            'command_endpoint_id'       => t('Endpoint Id')
        ];

        if (Backend::supportsDependencies()) {
            $columns['total_children'] = t('Total Children');
        }

        return $columns;
    }

    public function getSearchColumns()
    {
        return ['name_ci', 'display_name'];
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
            'flapping_enabled',
            'is_volatile'
        ]));

        $behaviors->add(new ReRoute([
            'child'         => 'to.from',
            'parent'        => 'from.to',
            'servicegroup'  => 'service.servicegroup',
            'user'          => 'notification.user',
            'usergroup'     => 'notification.usergroup'
        ]));

        $behaviors->add(new Binary([
            'id',
            'environment_id',
            'name_checksum',
            'properties_checksum',
            'address_bin',
            'address6_bin',
            'checkcommand_id',
            'check_timeperiod_id',
            'eventcommand_id',
            'action_url_id',
            'notes_url_id',
            'icon_image_id',
            'zone_id',
            'command_endpoint_id'
        ]));
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
        $relations->hasOne('state', HostState::class)->setJoinType('LEFT');
        $relations->hasOne('dependency_node', DependencyNode::class)->setJoinType('LEFT');

        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('eventcommand', Eventcommand::class);
        $relations->belongsTo('checkcommand', Checkcommand::class);
        $relations->belongsTo('timeperiod', Timeperiod::class)
            ->setCandidateKey('check_timeperiod_id')
            ->setJoinType('LEFT');
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
            ->through(HostCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->through(HostCustomvar::class);
        $relations->belongsToMany('vars', Vars::class)
            ->through(HostCustomvar::class);
        $relations->belongsToMany('hostgroup', Hostgroup::class)
            ->through(HostgroupMember::class);

        $relations->hasMany('comment', Comment::class)->setJoinType('LEFT');
        $relations->hasMany('downtime', Downtime::class)->setJoinType('LEFT');
        $relations->hasMany('history', History::class);
        $relations->hasMany('notification', Notification::class)->setJoinType('LEFT');
        $relations->hasMany('notification_history', NotificationHistory::class);
        $relations->hasMany('service', Service::class)->setJoinType('LEFT');

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
