<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Compat;

use Icinga\Module\Monitoring\Object\Host;

class CompatHost extends Host
{
    use CompatObject;

    private $legacyColumns = [
        'host_action_url'                       => ['path' => ['action_url', 'action_url']],
        'action_url'                            => ['path' => ['action_url', 'action_url']],
        'host_address'                          => ['path' => ['address']],
        'host_address6'                         => ['path' => ['address6']],
        'host_alias'                            => ['path' => ['display_name']],
        'host_check_interval'                   => ['path' => ['check_interval']],
        'host_display_name'                     => ['path' => ['display_name']],
        'host_icon_image'                       => ['path' => ['icon_image', 'icon_image']],
        'host_icon_image_alt'                   => ['path' => ['icon_image_alt']],
        'host_name'                             => ['path' => ['name']],
        'host_notes'                            => ['path' => ['notes']],
        'host_notes_url'                        => ['path' => ['notes_url', 'notes_url']],
        'host_acknowledged'                     => [
            'path' => ['state', 'is_acknowledged'],
            'type' => 'bool'
        ],
        'host_acknowledgement_type'             => [
            'path' => ['state', 'is_acknowledged'],
            'type' => 'bool'
        ],
        'host_active_checks_enabled'            => [
            'path' => ['active_checks_enabled'],
            'type' => 'bool'
        ],
        'host_active_checks_enabled_changed'    => null,
        'host_attempt'                          => null,
        'host_check_command'                    => ['path' => ['checkcommand_name']],
        'host_check_execution_time'             => ['path' => ['state', 'execution_time']],
        'host_check_latency'                    => ['path' => ['state', 'latency']],
        'host_check_source'                     => ['path' => ['state', 'check_source']],
        'host_check_timeperiod'                 => ['path' => ['check_timeperiod_name']],
        'host_current_check_attempt'            => ['path' => ['state', 'attempt']],
        'host_current_notification_number'      => null,
        'host_event_handler_enabled'            => [
            'path' => ['event_handler_enabled'],
            'type' => 'bool'
        ],
        'host_event_handler_enabled_changed'    => null,
        'host_flap_detection_enabled'           => [
            'path' => ['flapping_enabled'],
            'type' => 'bool'
        ],
        'host_flap_detection_enabled_changed'   => null,
        'host_handled'                          => [
            'path' => ['state', 'is_handled'],
            'type' => 'bool'
        ],
        'host_in_downtime'                      => [
            'path' => ['state', 'in_downtime'],
            'type' => 'bool'
        ],
        'host_is_flapping'                      => [
            'path' => ['state', 'is_flapping'],
            'type' => 'bool'
        ],
        'host_is_reachable'                     => [
            'path' => ['state', 'is_reachable'],
            'type' => 'bool'
        ],
        'host_last_check'                       => ['path' => ['state', 'last_update']],
        'host_last_notification'                => null,
        'host_last_state_change'                => ['path' => ['state', 'last_state_change']],
        'host_long_output'                      => ['path' => ['state', 'long_output']],
        'host_max_check_attempts'               => ['path' => ['max_check_attempts']],
        'host_next_check'                       => ['path' => ['state', 'next_check']],
        'host_next_update'                      => ['path' => ['state', 'next_update']],
        'host_notifications_enabled'            => [
            'path' => ['notifications_enabled'],
            'type' => 'bool'
        ],
        'host_notifications_enabled_changed'    => null,
        'host_obsessing'                        => null,
        'host_obsessing_changed'                => null,
        'host_output'                           => ['path' => ['state', 'output']],
        'host_passive_checks_enabled'           => [
            'path' => ['passive_checks_enabled'],
            'type' => 'bool'
        ],
        'host_passive_checks_enabled_changed'   => null,
        'host_percent_state_change'             => null,
        'host_perfdata'                         => [
            'path' => ['state', 'performance_data'],
            'type' => 'bool'
        ],
        'host_process_perfdata'                 => [
            'path' => ['perfdata_enabled'],
            'type' => 'bool'
        ],
        'host_state'                            => ['path' => ['state', 'soft_state']],
        'host_state_type'                       => ['path' => ['state', 'state_type']],
        'instance_name'                         => null
    ];
}
