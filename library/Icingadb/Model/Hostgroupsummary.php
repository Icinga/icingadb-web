<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\UnionModel;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Connection;
use ipl\Sql\Expression;
use ipl\Sql\Select;

class Hostgroupsummary extends UnionModel
{
    public static function on(Connection $db)
    {
        $q = parent::on($db);

        $q->on($q::ON_SELECT_ASSEMBLED, function (Select $select) use ($q) {
            $model = $q->getModel();

            $groupBy = $q->getResolver()->qualifyColumnsAndAliases((array) $model->getKeyName(), $model, false);

            // For PostgreSQL, ALL non-aggregate SELECT columns must appear in the GROUP BY clause:
            if ($q->getDb()->getAdapter() instanceof Pgsql) {
                /**
                 * Ignore Expressions, i.e. aggregate functions {@see getColumns()},
                 * which do not need to be added to the GROUP BY.
                 */
                $candidates = array_filter($select->getColumns(), 'is_string');
                // Remove already considered columns for the GROUP BY, i.e. the primary key.
                $candidates = array_diff_assoc($candidates, $groupBy);
                $groupBy = array_merge($groupBy, $candidates);
            }

            $select->groupBy($groupBy);
        });

        return $q;
    }

    public function getTableName()
    {
        return 'hostgroup';
    }

    public function getKeyName()
    {
        return ['id' => 'hostgroup_id'];
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
                'SUM(CASE WHEN host_id IS NOT NULL THEN 1 ELSE 0 END)'
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
            'hosts_severity'              => new Expression('MAX(host_severity)'),
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
                'SUM(CASE WHEN service_id IS NOT NULL THEN 1 ELSE 0 END)'
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

    public function getDefaultSort()
    {
        return 'display_name';
    }

    public function getUnions()
    {
        $unions = [
            [
                Host::class,
                [
                    'hostgroup',
                    'state'
                ],
                [
                    'hostgroup_id'           => 'hostgroup.id',
                    'hostgroup_name'         => 'hostgroup.name',
                    'hostgroup_display_name' => 'hostgroup.display_name',
                    'host_id'                => 'host.id',
                    'host_state'             => 'state.soft_state',
                    'host_handled'           => 'state.is_handled',
                    'host_severity'          => 'state.severity',
                    'host_is_in_downtime'    => 'state.in_downtime',
                    'host_is_acknowledged'   => 'state.is_acknowledged',
                    'service_id'             => new Expression('NULL'),
                    'service_state'          => new Expression('NULL'),
                    'service_handled'        => new Expression('NULL'),
                    'service_is_in_downtime' => new Expression('0'),
                    'service_is_acknowledged' => new Expression('0')
                ]
            ],
            [
                Service::class,
                [
                    'hostgroup',
                    'state'
                ],
                [
                    'hostgroup_id'           => 'hostgroup.id',
                    'hostgroup_name'         => 'hostgroup.name',
                    'hostgroup_display_name' => 'hostgroup.display_name',
                    'host_id'                => new Expression('NULL'),
                    'host_state'             => new Expression('NULL'),
                    'host_handled'           => new Expression('NULL'),
                    'host_severity'          => new Expression('0'),
                    'host_is_in_downtime'    => new Expression('0'),
                    'host_is_acknowledged'   => new Expression('0'),
                    'service_id'             => 'service.id',
                    'service_state'          => 'state.soft_state',
                    'service_handled'        => 'state.is_handled',
                    'service_is_in_downtime' => 'state.in_downtime',
                    'service_is_acknowledged' => 'state.is_acknowledged'
                ]
            ],
            [
                Hostgroup::class,
                [],
                [
                    'hostgroup_id'           => 'hostgroup.id',
                    'hostgroup_name'         => 'hostgroup.name',
                    'hostgroup_display_name' => 'hostgroup.display_name',
                    'host_id'                => new Expression('NULL'),
                    'host_state'             => new Expression('NULL'),
                    'host_handled'           => new Expression('NULL'),
                    'host_severity'          => new Expression('0'),
                    'host_is_in_downtime'    => new Expression('0'),
                    'host_is_acknowledged'   => new Expression('0'),
                    'service_id'             => new Expression('NULL'),
                    'service_state'          => new Expression('NULL'),
                    'service_handled'        => new Expression('NULL'),
                    'service_is_in_downtime' => new Expression('0'),
                    'service_is_acknowledged' => new Expression('0')
                ]
            ]
        ];

        return $unions;
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id'
        ]));
    }
}
