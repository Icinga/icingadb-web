<?php

namespace Icinga\Module\Eagle\Model;

use ipl\Orm\UnionModel;
use ipl\Sql\Expression;

class Hostgroupsummary extends UnionModel
{
    public function getTableName()
    {
        return 'summary';
    }

    public function getKeyName()
    {
        return 'hostgroup_id';
    }

    public function getColumns()
    {
        return [
            'display_name'                => 'hostgroup_display_name',
            'hosts_down_handled'          => new Expression(
                'SUM(CASE WHEN host_state = 1 AND host_handled = \'y\' THEN 1 ELSE 0 END)'
            ),
            'hosts_down_unhandled'        => new Expression(
                'SUM(CASE WHEN host_state = 1 AND host_handled = \'n\' THEN 1 ELSE 0 END)'
            ),
            'hosts_pending'               => new Expression(
                'SUM(CASE WHEN host_state = 99 THEN 1 ELSE 0 END)'
            ),
            'hosts_total'                 => new Expression(
                'SUM(CASE WHEN host_state IS NOT NULL THEN 1 ELSE 0 END)'
            ),
            'hosts_unreachable'           => new Expression(
                'SUM(CASE WHEN host_state = 2 THEN 1 ELSE 0 END)'
            ),
            'hosts_unreachable_handled'   => new Expression(
                'SUM(CASE WHEN host_state = 2 AND host_handled = \'y\' THEN 1 ELSE 0 END)'
            ),
            'hosts_unreachable_unhandled' => new Expression(
                'SUM(CASE WHEN host_state = 2 AND host_handled = \'n\' THEN 1 ELSE 0 END)'
            ),
            'hosts_up'                    => new Expression(
                'SUM(CASE WHEN host_state = 0 THEN 1 ELSE 0 END)'
            ),
            'name'                        => 'hostgroup_name',
            'services_critical_handled'   => new Expression(
                'SUM(CASE WHEN service_state = 2 AND service_handled = \'y\' THEN 1 ELSE 0 END)'
            ),
            'services_critical_unhandled' => new Expression(
                'SUM(CASE WHEN service_state = 2 AND service_handled = \'n\' THEN 1 ELSE 0 END)'
            ),
            'services_ok'                 => new Expression(
                'SUM(CASE WHEN service_state = 0 THEN 1 ELSE 0 END)'
            ),
            'services_pending'            => new Expression(
                'SUM(CASE WHEN service_state = 99 THEN 1 ELSE 0 END)'
            ),
            'services_total'              => new Expression(
                'SUM(CASE WHEN service_state IS NOT NULL THEN 1 ELSE 0 END)'
            ),
            'services_unknown_handled'    => new Expression(
                'SUM(CASE WHEN service_state = 3 AND service_handled = \'y\' THEN 1 ELSE 0 END)'
            ),
            'services_unknown_unhandled'  => new Expression(
                'SUM(CASE WHEN service_state = 3 AND service_handled = \'n\' THEN 1 ELSE 0 END)'
            ),
            'services_warning_handled'    => new Expression(
                'SUM(CASE WHEN service_state = 1 AND service_handled = \'y\' THEN 1 ELSE 0 END)'
            ),
            'services_warning_unhandled'  => new Expression(
                'SUM(CASE WHEN service_state = 1 AND service_handled = \'n\' THEN 1 ELSE 0 END)'
            )
        ];
    }

    public function getAggregateColumns()
    {
        return true;
    }

    public function getSearchColumns()
    {
        return ['display_name'];
    }

    public function getSortRules()
    {
        return ['display_name'];
    }

    public function getUnions()
    {
        $unions = [
            [
                Host::class,
                [
                    'hostgroup_id'           => 'hostgroup.id',
                    'hostgroup_name'         => 'hostgroup.name',
                    'hostgroup_display_name' => 'hostgroup.display_name',
                    'host_state'             => 'state.soft_state',
                    'host_handled'           => 'state.is_handled',
                    'service_state'          => new Expression('NULL'),
                    'service_handled'        => new Expression('NULL')
                ]
            ],
            [
                Service::class,
                [
                    'hostgroup_id'           => 'hostgroup.id',
                    'hostgroup_name'         => 'hostgroup.name',
                    'hostgroup_display_name' => 'hostgroup.display_name',
                    'host_state'             => new Expression('NULL'),
                    'host_handled'           => new Expression('NULL'),
                    'service_state'          => 'state.soft_state',
                    'service_handled'        => 'state.is_handled'
                ]
            ]
        ];

        return $unions;
    }
}
