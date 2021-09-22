<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Compat;

use Icinga\Module\Monitoring\Object\Service;

class CompatService extends Service
{
    use CompatObject;

    private $legacyColumns = [
        'instance_name'                             => null,
        'host_attempt'                              => null,
        'host_icon_image'                           => ['path' => ['host', 'icon_image', 'icon_image']],
        'host_icon_image_alt'                       => ['path' => ['host', 'icon_image_alt']],
        'host_acknowledged'                         => [
            'path' => ['host', 'state', 'is_acknowledged'],
            'type' => 'bool'
        ],
        'host_active_checks_enabled'                => [
            'path' => ['host', 'active_checks_enabled'],
            'type' => 'bool'
        ],
        'host_address'                              => ['path' => ['host', 'address']],
        'host_address6'                             => ['path' => ['host', 'address6']],
        'host_alias'                                => ['path' => ['host', 'display_name']],
        'host_display_name'                         => ['path' => ['host', 'display_name']],
        'host_handled'                              => [
            'path' => ['host', 'state', 'is_handled'],
            'type' => 'bool'
        ],
        'host_in_downtime'                          => [
            'path' => ['host', 'state', 'in_downtime'],
            'type' => 'bool'
        ],
        'host_is_flapping'                          => [
            'path' => ['host', 'state', 'is_flapping'],
            'type' => 'bool'
        ],
        'host_last_state_change'                    => ['path' => ['host', 'state', 'last_state_change']],
        'host_name'                                 => ['path' => ['host', 'name']],
        'host_notifications_enabled'                => [
            'path' => ['host', 'notifications_enabled'],
            'type' => 'bool'
        ],
        'host_passive_checks_enabled'               => [
            'path' => ['host', 'passive_checks_enabled'],
            'type' => 'bool'
        ],
        'host_state'                                => ['path' => ['host', 'state', 'soft_state']],
        'host_state_type'                           => ['path' => ['host', 'state', 'state_type']],
        'service_icon_image'                        => ['path' => ['icon_image', 'icon_image']],
        'service_icon_image_alt'                    => ['path' => ['icon_image_alt']],
        'service_acknowledged'                      => [
            'path' => ['state', 'is_acknowledged'],
            'type' => 'bool'
        ],
        'service_acknowledgement_type'              => [
            'path' => ['state', 'is_acknowledged'],
            'type' => 'bool'
        ],
        'service_action_url'                        => ['path' => ['action_url', 'action_url']],
        'action_url'                                => ['path' => ['action_url', 'action_url']],
        'service_active_checks_enabled'             => [
            'path' => ['active_checks_enabled'],
            'type' => 'bool'
        ],
        'service_active_checks_enabled_changed'     => null,
        'service_attempt'                           => null,
        'service_check_command'                     => ['path' => ['checkcommand']],
        'service_check_execution_time'              => ['path' => ['state', 'execution_time']],
        'service_check_interval'                    => ['path' => ['check_interval']],
        'service_check_latency'                     => ['path' => ['state', 'latency']],
        'service_check_source'                      => ['path' => ['state', 'check_source']],
        'service_check_timeperiod'                  => ['path' => ['check_timeperiod']],
        'service_current_notification_number'       => null,
        'service_description'                       => ['path' => ['name']],
        'service_display_name'                      => ['path' => ['display_name']],
        'service_event_handler_enabled'             => [
            'path' => ['event_handler_enabled'],
            'type' => 'bool'
        ],
        'service_event_handler_enabled_changed'     => null,
        'service_flap_detection_enabled'            => [
            'path' => ['flapping_enabled'],
            'type' => 'bool'
        ],
        'service_flap_detection_enabled_changed'    => null,
        'service_handled'                           => [
            'path' => ['state', 'is_handled'],
            'type' => 'bool'
        ],
        'service_in_downtime'                       => [
            'path' => ['state', 'in_downtime'],
            'type' => 'bool'
        ],
        'service_is_flapping'                       => [
            'path' => ['state', 'is_flapping'],
            'type' => 'bool'
        ],
        'service_is_reachable'                      => [
            'path' => ['state', 'is_reachable'],
            'type' => 'bool'
        ],
        'service_last_check'                        => ['path' => ['state', 'last_update']],
        'service_last_notification'                 => null,
        'service_last_state_change'                 => ['path' => ['state', 'last_state_change']],
        'service_long_output'                       => ['path' => ['state', 'long_output']],
        'service_next_check'                        => ['path' => ['state', 'next_check']],
        'service_next_update'                       => ['path' => ['state', 'next_update']],
        'service_notes'                             => ['path' => ['notes']],
        'service_notes_url'                         => ['path' => ['notes_url', 'notes_url']],
        'service_notifications_enabled'             => [
            'path' => ['notifications_enabled'],
            'type' => 'bool'
        ],
        'service_notifications_enabled_changed'     => null,
        'service_obsessing'                         => null,
        'service_obsessing_changed'                 => null,
        'service_output'                            => ['path' => ['state', 'output']],
        'service_passive_checks_enabled'            => [
            'path' => ['passive_checks_enabled'],
            'type' => 'bool'
        ],
        'service_passive_checks_enabled_changed'    => null,
        'service_percent_state_change'              => null,
        'service_perfdata'                          => [
            'path' => ['state', 'performance_data'],
            'type' => 'bool'
        ],
        'service_process_perfdata'                  => [
            'path' => ['perfdata_enabled'],
            'type' => 'bool'
        ],
        'service_state'                             => ['path' => ['state', 'soft_state']],
        'service_state_type'                        => ['path' => ['state', 'state_type']]
    ];

    /**
     * Get this service's host
     *
     * @return CompatHost
     */
    public function getHost(): CompatHost
    {
        if ($this->host === null) {
            $this->host = new CompatHost($this->object->host);
        }

        return $this->host;
    }

    protected function fetchHost()
    {
        $this->getHost();
    }
}
