<?php

namespace Icinga\Module\Icingadb\Compat;

use Icinga\Data\Filter\Filter;
use InvalidArgumentException;
use ipl\Web\Url;

class UrlMigrator
{
    const NO_YES = ['n', 'y'];
    const USE_EXPR = 'use-expr';
    const SORT_ONLY = 'sort-only';
    const DROP = 'drop';

    const SUPPORTED_PATHS = [
        'monitoring/list/hosts' => ['hosts', 'icingadb/hosts'],
        'monitoring/host/show'  => ['host', 'icingadb/host'],
        'monitoring/host/services' => ['host', 'icingadb/host/services'],
        'monitoring/host/history' => ['host', 'icingadb/host/history'],
        'monitoring/list/services' => ['services', 'icingadb/services'],
        'monitoring/service/show' => ['service', 'icingadb/service'],
        'monitoring/service/history' => ['service', 'icingadb/service/history'],
        'monitoring/list/hostgroups' => ['hostgroups', 'icingadb/hostgroups'],
        'monitoring/list/servicegroups' => ['servicegroups', 'icingadb/servicegroups'],
        'monitoring/list/contactgroups' => ['contactgroups', 'icingadb/usergroups'],
        'monitoring/list/contacts' => ['contacts', 'icingadb/users'],
        'monitoring/list/comments' => ['comments', 'icingadb/comments'],
        'monitoring/list/downtimes' => ['downtimes', 'icingadb/downtimes'],
        'monitoring/list/eventhistory' => ['history', 'icingadb/history'],
        'monitoring/list/notifications' => ['notificationHistory', 'icingadb/notifications']
    ];

    public static function isSupportedUrl(Url $url)
    {
        $supportedPaths = self::SUPPORTED_PATHS;
        return isset($supportedPaths[$url->getPath()]);
    }

    public static function hasQueryTransformer($name)
    {
        return method_exists(new self(), $name . 'Columns');
    }

    public static function transformUrl(Url $url)
    {
        if (! self::isSupportedUrl($url)) {
            throw new InvalidArgumentException(sprintf('Url path "%s" is not supported', $url->getPath()));
        }

        list($queryTransformer, $dbRoute) = self::SUPPORTED_PATHS[$url->getPath()];

        $url = clone $url;
        $url->setPath($dbRoute);

        if (! $url->getParams()->isEmpty()) {
            $filter = Filter::fromQueryString((string) $url->getParams());
            $filter = self::transformFilter($filter, $queryTransformer);
            $url->setQueryString($filter ? $filter->toQueryString() : '');
        }

        return $url;
    }

    public static function transformFilter(Filter $filter, $queryTransformer = null)
    {
        $transformer = new self();

        $columns = $transformer::commonColumns();
        if ($queryTransformer !== null) {
            if (! self::hasQueryTransformer($queryTransformer)) {
                throw new InvalidArgumentException(sprintf('Transformer "%s" is not supported', $queryTransformer));
            }

            $columns = array_merge($columns, $transformer->{$queryTransformer . 'Columns'}());
        }

        $rewritten = $transformer->rewrite($filter, $columns);
        return $rewritten === false ? false : ($rewritten instanceof Filter ? $rewritten : $filter);
    }

    protected function rewrite(Filter $filter, array $legacyColumns, $parent = null)
    {
        $rewritten = null;
        if ($filter->isExpression()) {
            $column = $filter->getColumn();

            if (isset($legacyColumns[$column])) {
                if ($legacyColumns[$column] === self::DROP) {
                    return false;
                } elseif (is_callable($legacyColumns[$column])) {
                    return $legacyColumns[$column]($filter);
                } elseif (! is_array($legacyColumns[$column])) {
                    return null;
                }

                foreach ($legacyColumns[$column] as $modelPath => $exprRule) {
                    break;
                }

                $rewritten = $filter->setColumn($modelPath);

                switch (true) {
                    case $exprRule === self::USE_EXPR:
                        break;
                    case is_array($exprRule) && isset($exprRule[$filter->getExpression()]):
                        $filter->setExpression($exprRule[$filter->getExpression()]);
                        break;
                    default:
                        $filter->setExpression($exprRule);
                }
            } elseif ($column === 'sort') {
                $column = $filter->getExpression();
                if (isset($legacyColumns[$column])) {
                    if ($legacyColumns[$column] === self::DROP) {
                        return false;
                    } elseif (! is_array($legacyColumns[$column])) {
                        return $rewritten;
                    }

                    $column = key($legacyColumns[$column]);

                    $rewritten = $filter->setExpression($column);
                }

                if ($parent !== null) {
                    foreach ($parent->filters() as $child) {
                        if ($child->isExpression() && $child->getColumn() === 'dir') {
                            $dir = $child->getExpression();

                            $rewritten = $filter->setExpression("{$column} {$dir}");

                            $parent->removeId($child->getId());
                        }
                    }
                }
            } elseif ($column === 'dir') {
                if ($parent !== null) {
                    foreach ($parent->filters() as $child) {
                        if ($child->isExpression() && $child->getColumn() === 'sort') {
                            return null;
                        }
                    }
                }

                return false;
            } elseif (preg_match('/^_(host|service)_([\w.]+)/i', $column, $groups)) {
                $rewritten = $filter->setColumn($groups[1] . '.vars.' . $groups[2]);
            }
        } else {
            foreach ($filter->filters() as $child) {
                $retVal = $this->rewrite($child->isExpression() ? clone $child : $child, $legacyColumns, $filter);
                if ($retVal === false) {
                    $filter->removeId($child->getId());
                } elseif ($retVal instanceof Filter) {
                    $filter->replaceById($child->getId(), $retVal);
                }
            }
        }

        return $rewritten;
    }

    protected static function commonColumns()
    {
        return [

            // Filter columns
            'host' => [
                'host.name_ci' => self::USE_EXPR
            ],
            'host_display_name' => [
                'host.display_name' => self::USE_EXPR
            ],
            'host_alias' => self::DROP,
            'hostgroup' => [
                'hostgroup.name_ci' => self::USE_EXPR
            ],
            'hostgroup_alias' => [
                'hostgroup.display_name' => self::USE_EXPR
            ],
            'service' => [
                'service.name_ci' => self::USE_EXPR
            ],
            'service_display_name' => [
                'service.display_name' => self::USE_EXPR
            ],
            'servicegroup' => [
                'servicegroup.name_ci' => self::USE_EXPR
            ],
            'servicegroup_alias' => [
                'servicegroup.display_name' => self::USE_EXPR
            ],

            // Restriction columns
            'instance_name' => self::DROP,
            'host_name' => [
                'host.name' => self::USE_EXPR
            ],
            'hostgroup_name' => [
                'hostgroup.name' => self::USE_EXPR
            ],
            'service_description' => [
                'service.name' => self::USE_EXPR
            ],
            'servicegroup_name' => [
                'servicegroup.name' => self::USE_EXPR
            ]
        ];
    }

    protected static function hostsColumns()
    {
        return [

            // Query columns
            'host_acknowledged' => [
                'host.state.is_acknowledged' => self::NO_YES
            ],
            'host_acknowledgement_type' => [
                'host.state.is_acknowledged' => array_merge(self::NO_YES, ['sticky'])
            ],
            'host_action_url' => [
                'host.action_url.action_url' => self::USE_EXPR
            ],
            'host_active_checks_enabled' => [
                'host.active_checks_enabled' => self::NO_YES
            ],
            'host_active_checks_enabled_changed' => self::DROP,
            'host_address' => [
                'host.address' => self::USE_EXPR
            ],
            'host_address6' => [
                'host.address6' => self::USE_EXPR
            ],
            'host_alias' => self::DROP,
            'host_check_command' => [
                'host.checkcommand' => self::USE_EXPR
            ],
            'host_check_execution_time' => [
                'host.state.execution_time' => self::USE_EXPR
            ],
            'host_check_latency' => [
                'host.state.latency' => self::USE_EXPR
            ],
            'host_check_source' => [
                'host.state.check_source' => self::USE_EXPR
            ],
            'host_check_timeperiod' => [
                'host.check_timeperiod' => self::USE_EXPR
            ],
            'host_current_check_attempt' => [
                'host.state.attempt' => self::USE_EXPR
            ],
            'host_current_notification_number' => self::DROP,
            'host_display_name' => [
                'host.display_name' => self::USE_EXPR
            ],
            'host_event_handler_enabled' => [
                'host.event_handler_enabled' => self::NO_YES
            ],
            'host_event_handler_enabled_changed' => self::DROP,
            'host_flap_detection_enabled' => [
                'host.flapping_enabled' => self::NO_YES
            ],
            'host_flap_detection_enabled_changed' => self::DROP,
            'host_handled' => [
                'host.state.is_handled' => self::NO_YES
            ],
            'host_hard_state' => [
                'host.state.hard_state' => self::USE_EXPR
            ],
            'host_in_downtime' => [
                'host.state.in_downtime' => self::NO_YES
            ],
            'host_ipv4' => [
                'host.address_bin' => self::USE_EXPR
            ],
            'host_is_flapping' => [
                'host.state.is_flapping' => self::NO_YES
            ],
            'host_is_reachable' => [
                'host.state.is_reachable' => self::NO_YES
            ],
            'host_last_check' => [
                'host.state.last_update' => self::USE_EXPR
            ],
            'host_last_notification' => self::DROP,
            'host_last_state_change' => [
                'host.state.last_state_change' => self::USE_EXPR
            ],
            'host_last_state_change_ts' => [
                'host.state.last_state_change' => self::USE_EXPR
            ],
            'host_long_output' => [
                'host.state.long_output' => self::USE_EXPR
            ],
            'host_max_check_attempts' => [
                'host.max_check_attempts' => self::USE_EXPR
            ],
            'host_modified_host_attributes' => self::DROP,
            'host_name' => [
                'host.name' => self::USE_EXPR
            ],
            'host_next_check' => [
                'host.state.next_check' => self::USE_EXPR
            ],
            'host_notes_url' => [
                'host.notes_url.notes_url' => self::USE_EXPR
            ],
            'host_notifications_enabled' => [
                'host.notifications_enabled' => self::NO_YES
            ],
            'host_notifications_enabled_changed' => self::DROP,
            'host_obsessing' => self::DROP,
            'host_obsessing_changed' => self::DROP,
            'host_output' => [
                'host.state.output' => self::USE_EXPR
            ],
            'host_passive_checks_enabled' => [
                'host.passive_checks_enabled' => self::NO_YES
            ],
            'host_passive_checks_enabled_changed' => self::DROP,
            'host_percent_state_change' => self::DROP,
            'host_perfdata' => [
                'host.state.performance_data' => self::USE_EXPR
            ],
            'host_problem' => [
                'host.state.is_problem' => self::NO_YES
            ],
            'host_severity' => [
                'host.state.severity' => self::USE_EXPR
            ],
            'host_state' => [
                'host.state.soft_state' => self::USE_EXPR
            ],
            'host_state_type' => [
                'host.state.state_type' => ['soft', 'hard']
            ],
            'host_unhandled' => [
                'host.state.is_handled' => array_reverse(self::NO_YES)
            ],

            // Filter columns
            'host_contact' => [
                'host.user.name' => self::USE_EXPR
            ],
            'host_contactgroup' => [
                'host.usergroup.name' => self::USE_EXPR
            ],

            // Query columns the dataview doesn't include, added here because it's possible to filter for them anyway
            'host_check_interval' => self::DROP,
            'host_icon_image' => self::DROP,
            'host_icon_image_alt' => self::DROP,
            'host_notes' => self::DROP,
            'object_type' => self::DROP,
            'object_id' => self::DROP,
            'host_attempt' => self::DROP,
            'host_check_type' => self::DROP,
            'host_event_handler' => self::DROP,
            'host_failure_prediction_enabled' => self::DROP,
            'host_is_passive_checked' => self::DROP,
            'host_last_hard_state' => self::DROP,
            'host_last_hard_state_change' => self::DROP,
            'host_last_time_down' => self::DROP,
            'host_last_time_unreachable' => self::DROP,
            'host_last_time_up' => self::DROP,
            'host_next_notification' => self::DROP,
            'host_next_update' => [
                // Doesn't get dropped because there's a default dashlet using it..
                'host.state.next_update' => self::USE_EXPR
            ],
            'host_no_more_notifications' => self::DROP,
            'host_normal_check_interval' => self::DROP,
            'host_problem_has_been_acknowledged' => self::DROP,
            'host_process_performance_data' => self::DROP,
            'host_retry_check_interval' => self::DROP,
            'host_scheduled_downtime_depth' => self::DROP,
            'host_status_update_time' => self::DROP,
            'problems' => self::DROP
        ];
    }

    protected static function hostColumns()
    {
        return [
            'host' => [
                'name' => self::USE_EXPR
            ]
        ];
    }

    protected static function servicesColumns()
    {
        return [

            // Query columns
            'host_acknowledged' => [
                'host.state.is_acknowledged' => self::NO_YES
            ],
            'host_action_url' => [
                'host.action_url.action_url' => self::USE_EXPR
            ],
            'host_active_checks_enabled' => [
                'host.active_checks_enabled' => self::NO_YES
            ],
            'host_address' => [
                'host.address' => self::USE_EXPR
            ],
            'host_address6' => [
                'host.address6' => self::USE_EXPR
            ],
            'host_alias' => self::DROP,
            'host_check_source' => [
                'host.state.check_source' => self::USE_EXPR
            ],
            'host_display_name' => [
                'host.display_name' => self::USE_EXPR
            ],
            'host_handled' => [
                'host.state.is_handled' => self::NO_YES
            ],
            'host_hard_state' => [
                'host.state.hard_state' => self::USE_EXPR
            ],
            'host_in_downtime' => [
                'host.state.in_downtime' => self::NO_YES
            ],
            'host_ipv4' => [
                'host.address_bin' => self::USE_EXPR
            ],
            'host_is_flapping' => [
                'host.state.is_flapping' => self::NO_YES
            ],
            'host_last_check' => [
                'host.state.last_update' => self::USE_EXPR
            ],
            'host_last_hard_state' => [
                'host.state.previous_hard_state' => self::USE_EXPR
            ],
            'host_last_hard_state_change' => self::DROP,
            'host_last_state_change' => [
                'host.state.last_state_change' => self::USE_EXPR
            ],
            'host_last_time_down' => self::DROP,
            'host_last_time_unreachable' => self::DROP,
            'host_last_time_up' => self::DROP,
            'host_long_output' => [
                'host.state.long_output' => self::USE_EXPR
            ],
            'host_modified_host_attributes' => self::DROP,
            'host_notes_url' => [
                'host.notes_url.notes_url' => self::USE_EXPR
            ],
            'host_notifications_enabled' => [
                'host.notifications_enabled' => self::NO_YES
            ],
            'host_output' => [
                'host.state.output' => self::USE_EXPR
            ],
            'host_passive_checks_enabled' => [
                'host.passive_checks_enabled' => self::NO_YES
            ],
            'host_perfdata' => [
                'host.state.performance_data' => self::USE_EXPR
            ],
            'host_problem' => [
                'host.state.is_problem' => self::NO_YES
            ],
            'host_severity' => [
                'host.state.severity' => self::USE_EXPR
            ],
            'host_state' => [
                'host.state.soft_state' => self::USE_EXPR
            ],
            'host_state_type' => [
                'host.state.state_type' => ['soft', 'hard']
            ],
            'service_acknowledged' => [
                'service.state.is_acknowledged' => self::NO_YES
            ],
            'service_acknowledgement_type' => [
                'service.state.is_acknowledged' => array_merge(self::NO_YES, ['sticky'])
            ],
            'service_action_url' => [
                'service.action_url.action_url' => self::USE_EXPR
            ],
            'service_active_checks_enabled' => [
                'service.active_checks_enabled' => self::NO_YES
            ],
            'service_active_checks_enabled_changed' => self::DROP,
            'service_attempt' => [
                'service.state.attempt' => self::USE_EXPR
            ],
            'service_check_command' => [
                'service.checkcommand' => self::USE_EXPR
            ],
            'service_check_source' => [
                'service.state.check_source' => self::USE_EXPR
            ],
            'service_check_timeperiod' => [
                'service.check_timeperiod' => self::USE_EXPR
            ],
            'service_current_check_attempt' => [
                'service.state.attempt' => self::USE_EXPR
            ],
            'service_current_notification_number' => self::DROP,
            'service_display_name' => [
                'service.display_name' => self::USE_EXPR
            ],
            'service_event_handler_enabled' => [
                'service.event_handler_enabled' => self::NO_YES
            ],
            'service_event_handler_enabled_changed' => self::DROP,
            'service_flap_detection_enabled' => [
                'service.flapping_enabled' => self::NO_YES
            ],
            'service_flap_detection_enabled_changed' => self::DROP,
            'service_handled' => [
                'service.state.is_handled' => self::NO_YES
            ],
            'service_hard_state' => [
                'service.state.hard_state' => self::USE_EXPR
            ],
            'service_host_name' => [
                'host.name' => self::USE_EXPR
            ],
            'service_in_downtime' => [
                'service.state.in_downtime' => self::NO_YES
            ],
            'service_is_flapping' => [
                'service.state.is_flapping' => self::NO_YES
            ],
            'service_is_reachable' => [
                'service.state.is_reachable' => self::NO_YES
            ],
            'service_last_check' => [
                'service.state.last_update' => self::USE_EXPR
            ],
            'service_last_hard_state' => [
                'service.state.previous_hard_state' => self::USE_EXPR
            ],
            'service_last_hard_state_change' => self::DROP,
            'service_last_notification' => self::DROP,
            'service_last_state_change' => [
                'service.state.last_state_change' => self::USE_EXPR
            ],
            'service_last_state_change_ts' => [
                'service.state.last_state_change' => self::USE_EXPR
            ],
            'service_last_time_critical' => self::DROP,
            'service_last_time_ok' => self::DROP,
            'service_last_time_unknown' => self::DROP,
            'service_last_time_warning' => self::DROP,
            'service_long_output' => [
                'service.state.long_output' => self::USE_EXPR
            ],
            'service_max_check_attempts' => [
                'service.max_check_attempts' => self::USE_EXPR
            ],
            'service_modified_service_attributes' => self::DROP,
            'service_next_check' => [
                'service.state.next_check' => self::USE_EXPR
            ],
            'service_notes' => [
                'service.notes' => self::USE_EXPR
            ],
            'service_notes_url' => [
                'service.notes_url.notes_url' => self::USE_EXPR
            ],
            'service_notifications_enabled' => [
                'service.notifications_enabled' => self::NO_YES
            ],
            'service_notifications_enabled_changed' => self::DROP,
            'service_obsessing' => self::DROP,
            'service_obsessing_changed' => self::DROP,
            'service_output' => [
                'service.state.output' => self::USE_EXPR
            ],
            'service_passive_checks_enabled' => [
                'service.passive_checks_enabled' => self::USE_EXPR
            ],
            'service_passive_checks_enabled_changed' => self::DROP,
            'service_perfdata' => [
                'service.state.performance_data' => self::USE_EXPR
            ],
            'service_problem' => [
                'service.state.is_problem' => self::NO_YES
            ],
            'service_severity' => [
                'service.state.severity' => self::USE_EXPR
            ],
            'service_state' => [
                'service.state.soft_state' => self::USE_EXPR
            ],
            'service_state_type' => [
                'service.state.state_type' => ['soft', 'hard']
            ],
            'service_unhandled' => [
                'service.state.is_handled' => array_reverse(self::NO_YES)
            ],

            // Filter columns
            'host_contact' => [
                'host.user.name' => self::USE_EXPR
            ],
            'host_contactgroup' => [
                'host.usergroup.name' => self::USE_EXPR
            ],
            'service_contact' => [
                'service.user.name' => self::USE_EXPR
            ],
            'service_contactgroup' => [
                'service.usergroup.name' => self::USE_EXPR
            ],
            'service_host' => [
                'host.name_ci' => self::USE_EXPR
            ],

            // Query columns the dataview doesn't include, added here because it's possible to filter for them anyway
            'host_icon_image' => self::DROP,
            'host_icon_image_alt' => self::DROP,
            'host_notes' => self::DROP,
            'host_acknowledgement_type' => self::DROP,
            'host_active_checks_enabled_changed' => self::DROP,
            'host_attempt' => self::DROP,
            'host_check_command' => self::DROP,
            'host_check_execution_time' => self::DROP,
            'host_check_latency' => self::DROP,
            'host_check_timeperiod_object_id' => self::DROP,
            'host_check_type' => self::DROP,
            'host_current_check_attempt' => self::DROP,
            'host_current_notification_number' => self::DROP,
            'host_event_handler' => self::DROP,
            'host_event_handler_enabled' => self::DROP,
            'host_event_handler_enabled_changed' => self::DROP,
            'host_failure_prediction_enabled' => self::DROP,
            'host_flap_detection_enabled' => self::DROP,
            'host_flap_detection_enabled_changed' => self::DROP,
            'host_is_reachable' => self::DROP,
            'host_last_notification' => self::DROP,
            'host_max_check_attempts' => self::DROP,
            'host_next_check' => self::DROP,
            'host_next_notification' => self::DROP,
            'host_no_more_notifications' => self::DROP,
            'host_normal_check_interval' => self::DROP,
            'host_notifications_enabled_changed' => self::DROP,
            'host_obsessing' => self::DROP,
            'host_obsessing_changed' => self::DROP,
            'host_passive_checks_enabled_changed' => self::DROP,
            'host_percent_state_change' => self::DROP,
            'host_problem_has_been_acknowledged' => self::DROP,
            'host_process_performance_data' => self::DROP,
            'host_retry_check_interval' => self::DROP,
            'host_scheduled_downtime_depth' => self::DROP,
            'host_status_update_time' => self::DROP,
            'host_unhandled' => self::DROP,
            'object_type' => self::DROP,
            'service_check_interval' => self::DROP,
            'service_icon_image' => self::DROP,
            'service_icon_image_alt' => self::DROP,
            'service_check_execution_time' => self::DROP,
            'service_check_latency' => self::DROP,
            'service_check_timeperiod_object_id' => self::DROP,
            'service_check_type' => self::DROP,
            'service_event_handler' => self::DROP,
            'service_failure_prediction_enabled' => self::DROP,
            'service_is_passive_checked' => self::DROP,
            'service_next_notification' => self::DROP,
            'service_next_update' => [
                // Doesn't get dropped because there's a default dashlet using it..
                'service.state.next_update' => self::USE_EXPR
            ],
            'service_no_more_notifications' => self::DROP,
            'service_normal_check_interval' => self::DROP,
            'service_percent_state_change' => self::DROP,
            'service_problem_has_been_acknowledged' => self::DROP,
            'service_process_performance_data' => self::DROP,
            'service_retry_check_interval' => self::DROP,
            'service_scheduled_downtime_depth' => self::DROP,
            'service_status_update_time' => self::DROP,
            'problems' => self::DROP,
        ];
    }

    protected static function serviceColumns()
    {
        return [
            'host' => [
                'host.name' => self::USE_EXPR
            ],
            'service' => [
                'name' => self::USE_EXPR
            ]
        ];
    }

    protected static function hostgroupsColumns()
    {
        return [

            // Query columns
            'hostgroup_alias' => [
                'display_name' => self::USE_EXPR
            ],
            'hosts_severity' => self::SORT_ONLY,
            'hosts_total' => self::SORT_ONLY,
            'services_total' => self::SORT_ONLY,

            // Filter columns
            'host_contact' => [
                'host.user.name' => self::USE_EXPR
            ],
            'host_contactgroup' => [
                'host.usergroup.name' => self::USE_EXPR
            ]
        ];
    }

    protected static function servicegroupsColumns()
    {
        return [

            // Query columns
            'services_severity' => self::SORT_ONLY,
            'services_total' => self::SORT_ONLY,
            'servicegroup_alias' => [
                'display_name' => self::USE_EXPR
            ],

            // Filter columns
            'host_contact' => [
                'host.user.name' => self::USE_EXPR
            ],
            'host_contactgroup' => [
                'host.usergroup.name' => self::USE_EXPR
            ],
            'service_contact' => [
                'service.user.name' => self::USE_EXPR
            ],
            'service_contactgroup' => [
                'service.usergroup.name' => self::USE_EXPR
            ]
        ];
    }

    protected static function contactgroupsColumns()
    {
        return [

            // Query columns
            'contactgroup_name' => [
                'usergroup.name' => self::USE_EXPR
            ],
            'contactgroup_alias' => [
                'usergroup.display_name' => self::USE_EXPR
            ],
            'contact_count' => self::DROP,

            // Filter columns
            'contactgroup' => [
                'usergroup.name_ci' => self::USE_EXPR
            ]
        ];
    }

    protected static function contactsColumns()
    {
        $receivesStateNotifications = function ($state, $type = null) {
            return function ($filter) use ($state, $type) {
                /** @var \Icinga\Data\Filter\FilterExpression $filter */
                $negate = $filter->getSign() === '!=';
                switch ($filter->getExpression()) {
                    case '0':
                        $filter = Filter::matchAny(
                            Filter::where('user.notifications_enabled', 'n'),
                            Filter::expression('user.states', '!=', $state)
                        );
                        if ($type !== null) {
                            $filter->addFilter(Filter::expression('user.types', '!=', $type));
                        }

                        break;
                    case '1':
                        $filter = Filter::matchAll(
                            Filter::where('user.notifications_enabled', 'y'),
                            Filter::where('user.states', $state)
                        );
                        if ($type !== null) {
                            $filter->addFilter(Filter::where('user.types', $type));
                        }

                        break;
                    default:
                        return null;
                }

                if ($negate) {
                    $filter = Filter::not($filter);
                }

                return $filter;
            };
        };

        return [

            // Query columns
            'contact_object_id' => self::DROP,
            'contact_id' => [
                'user.id' => self::USE_EXPR
            ],
            'contact_name' => [
                'user.name' => self::USE_EXPR
            ],
            'contact_alias' => [
                'user.display_name' => self::USE_EXPR
            ],
            'contact_email' => [
                'user.email' => self::USE_EXPR
            ],
            'contact_pager' => [
                'user.pager' => self::USE_EXPR
            ],
            'contact_has_host_notfications' => $receivesStateNotifications(['up', 'down']),
            'contact_has_service_notfications' => $receivesStateNotifications(['ok', 'warning', 'critical', 'unknown']),
            'contact_can_submit_commands' => self::DROP,
            'contact_notify_service_recovery' => $receivesStateNotifications(
                ['ok', 'warning', 'critical', 'unknown'],
                'recovery'
            ),
            'contact_notify_service_warning' => $receivesStateNotifications('warning'),
            'contact_notify_service_critical' => $receivesStateNotifications('critical'),
            'contact_notify_service_unknown' => $receivesStateNotifications('unknown'),
            'contact_notify_service_flapping' => $receivesStateNotifications(
                ['ok', 'warning', 'critical', 'unknown'],
                ['flapping_start', 'flapping_end']
            ),
            'contact_notify_service_downtime' => $receivesStateNotifications(
                ['ok', 'warning', 'critical', 'unknown'],
                ['downtime_start', 'downtime_end', 'downtime_removed']
            ),
            'contact_notify_host_recovery' => $receivesStateNotifications(['up', 'down'], 'recovery'),
            'contact_notify_host_down' => $receivesStateNotifications('down'),
            'contact_notify_host_unreachable' => self::DROP,
            'contact_notify_host_flapping' => $receivesStateNotifications(
                ['up', 'down'],
                ['flapping_start', 'flapping_end']
            ),
            'contact_notify_host_downtime' => $receivesStateNotifications(
                ['up', 'down'],
                ['downtime_start', 'downtime_end', 'downtime_removed']
            ),
            'contact_notify_host_timeperiod' => function ($filter) {
                /** @var \Icinga\Data\Filter\FilterExpression $filter */
                $filter->setColumn('user.timeperiod.name_ci');
                return $filter->andFilter(
                    Filter::expression('user.states', '=', ['up', 'down'])
                );
            },
            'contact_notify_service_timeperiod' => function ($filter) {
                /** @var \Icinga\Data\Filter\FilterExpression $filter */
                $filter->setColumn('user.timeperiod.name_ci');
                return $filter->andFilter(
                    Filter::expression('user.states', '=', ['ok', 'warning', 'critical', 'unknown'])
                );
            },

            // Filter columns
            'contact' => [
                'user.name_ci' => self::USE_EXPR
            ],
            'contactgroup' => [
                'usergroup.name_ci' => self::USE_EXPR
            ],
            'contactgroup_name' => [
                'usergroup.name' => self::USE_EXPR
            ],
            'contactgroup_alias' => [
                'usergroup.display_name' => self::USE_EXPR
            ]
        ];
    }

    protected static function commentsColumns()
    {
        return [

            // Query columns
            'comment_author_name' => [
                'comment.author' => self::USE_EXPR
            ],
            'comment_data' => [
                'comment.text' => self::USE_EXPR
            ],
            'comment_expiration' => [
                'comment.expire_time' => self::USE_EXPR
            ],
            'comment_internal_id' => self::DROP,
            'comment_is_persistent' => [
                'comment.is_persistent' => self::NO_YES
            ],
            'comment_name' => [
                'comment.name' => self::USE_EXPR
            ],
            'comment_timestamp' => [
                'comment.entry_time' => self::USE_EXPR
            ],
            'comment_type' => [
                'comment.entry_type' => self::USE_EXPR
            ],
            'host_display_name' => [
                'host.display_name' => self::USE_EXPR
            ],
            'object_type' => [
                'comment.object_type' => self::USE_EXPR
            ],
            'service_display_name' => [
                'service.display_name' => self::USE_EXPR
            ],
            'service_host_name' => [
                'host.name' => self::USE_EXPR
            ],

            // Filter columns
            'comment_author' => [
                'comment.author' => self::USE_EXPR
            ]
        ];
    }

    protected static function downtimesColumns()
    {
        return [

            // Query columns
            'downtime_author_name' => [
                'downtime.author' => self::USE_EXPR
            ],
            'downtime_comment' => [
                'downtime.comment' => self::USE_EXPR
            ],
            'downtime_duration' => [
                'downtime.flexible_duration' => self::USE_EXPR
            ],
            'downtime_end' => [
                'downtime.end_time' => self::USE_EXPR
            ],
            'downtime_entry_time' => [
                'downtime.entry_time' => self::USE_EXPR
            ],
            'downtime_internal_id' => self::DROP,
            'downtime_is_fixed' => [
                'downtime.is_flexible' => array_reverse(self::NO_YES)
            ],
            'downtime_is_flexible' => [
                'downtime.is_flexible' => self::NO_YES
            ],
            'downtime_is_in_effect' => [
                'downtime.is_in_effect' => self::NO_YES
            ],
            'downtime_name' => [
                'downtime.name' => self::USE_EXPR
            ],
            'downtime_scheduled_end' => [
                'downtime.scheduled_end_time' => self::USE_EXPR
            ],
            'downtime_scheduled_start' => [
                'downtime.scheduled_start_time' => self::USE_EXPR
            ],
            'downtime_start' => [
                'downtime.start_time' => self::USE_EXPR
            ],
            'host_display_name' => [
                'host.display_name' => self::USE_EXPR
            ],
            'host_state' => [
                'host.state.soft_state' => self::USE_EXPR
            ],
            'object_type' => [
                'downtime.object_type' => self::USE_EXPR
            ],
            'service_display_name' => [
                'service.display_name' => self::USE_EXPR
            ],
            'service_host_name' => [
                'host.name' => self::USE_EXPR
            ],
            'service_state' => [
                'service.state.soft_state' => self::USE_EXPR
            ],

            // Filter columns
            'downtime_author' => [
                'downtime.author' => self::USE_EXPR
            ]
        ];
    }

    protected static function historyColumns()
    {
        return [

            // Query columns
            'id' => self::DROP,
            'object_type' => [
                'history.object_type' => self::USE_EXPR
            ],
            'timestamp' => [
                'history.event_time' => self::USE_EXPR
            ],
            'state' => [
                'history.state.soft_state' => self::USE_EXPR
            ],
            'output' => [
                'history.state.output' => self::USE_EXPR
            ],
            'type' => function ($filter) {
                /** @var \Icinga\Data\Filter\FilterExpression $filter */
                $expr = $filter->getExpression();

                switch (true) {
                    // NotificationhistoryQuery
                    case substr($expr, 0, 13) === 'notification_':
                        $filter->setColumn('history.notification.type');
                        $filter->setExpression([
                            'notification_ack' => 'acknowledgement',
                            'notification_flapping' => 'flapping_start',
                            'notification_flapping_end' => 'flapping_end',
                            'notification_dt_start' => 'downtime_start',
                            'notification_dt_end' => 'downtime_end',
                            'notification_custom' => 'custom',
                            'notification_state' => ['problem', 'recovery']
                        ][$expr]);
                        return $filter->andFilter(Filter::where('history.event_type', 'notification'));
                    // StatehistoryQuery
                    case in_array($expr, ['soft_state', 'hard_state'], true):
                        $filter->setColumn('history.state.state_type');
                        $filter->setExpression(substr($expr, 0, 4));
                        return $filter->andFilter(Filter::where('history.event_type', 'state_change'));
                     // DowntimestarthistoryQuery and DowntimeendhistoryQuery
                    case in_array($expr, ['dt_start', 'dt_end'], true):
                        $filter->setColumn('history.event_type');
                        $filter->setExpression('downtime_' . substr($expr, 3));
                        return $filter;
                    // CommenthistoryQuery
                    case in_array($expr, ['comment', 'ack'], true):
                        $filter->setColumn('history.comment.entry_type');
                        return $filter->andFilter(Filter::where('history.event_type', 'comment_add'));
                    // CommentdeletionhistoryQuery
                    case in_array($expr, ['comment_deleted', 'ack_deleted'], true):
                        $filter->setColumn('history.comment.entry_type');
                        return $filter->andFilter(Filter::where('history.event_type', 'comment_remove'));
                    // FlappingstarthistoryQuery and CommenthistoryQuery
                    case in_array($expr, ['flapping', 'flapping_deleted'], true):
                        $filter->setColumn('history.event_type');
                        return $filter->setExpression($expr === 'flapping' ? 'flapping_start' : 'flapping_end');
                }
            }
        ];
    }

    protected static function notificationHistoryColumns()
    {
        return [

            // Query columns
            'notification_contact_name' => [
                'notification_history.user.name' => self::USE_EXPR
            ],
            'notification_output' => [
                'notification_history.text' => self::USE_EXPR
            ],
            'notification_reason' => [
                'notification_history.type' => [
                    0 => ['problem', 'recovery'],
                    1 => 'acknowledgement',
                    2 => 'flapping_start',
                    3 => 'flapping_end',
                    5 => 'downtime_start',
                    6 => 'downtime_end',
                    7 => 'downtime_removed',
                    8 => 'custom' // ido schema doc says it's `99`, icinga2 though uses `8`
                ]
            ],
            'notification_state' => [
                'notification_history.state' => self::USE_EXPR
            ],
            'notification_timestamp' => [
                'notification_history.send_time' => self::USE_EXPR
            ],
            'object_type' => [
                'notification_history.object_type' => self::USE_EXPR
            ],
            'service_host_name' => [
                'host.name' => self::USE_EXPR
            ]
        ];
    }
}
