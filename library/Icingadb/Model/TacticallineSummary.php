<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;
use ipl\Orm\UnionModel;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Connection;
use ipl\Sql\Expression;
use ipl\Sql\Select;

class TacticallineSummary extends UnionModel
{
    public static function on(Connection $db)
    {
        $q = parent::on($db);

        $q->on($q::ON_SELECT_ASSEMBLED, function (Select $select) use ($q) {
            $model = $q->getModel();

#            $groupBy = $q->getResolver()->qualifyColumnsAndAliases((array) $model->getKeyName(), $model, false);
#
#            // For PostgreSQL, ALL non-aggregate SELECT columns must appear in the GROUP BY clause:
#            if ($q->getDb()->getAdapter() instanceof Pgsql) {
#                /**
#                 * Ignore Expressions, i.e. aggregate functions {@see getColumns()},
#                 * which do not need to be added to the GROUP BY.
#                 */
#                $candidates = array_filter($select->getColumns(), 'is_string');
#                // Remove already considered columns for the GROUP BY, i.e. the primary key.
#                $candidates = array_diff_assoc($candidates, $groupBy);
#                $groupBy = array_merge($groupBy, $candidates);
#           }
#
#            $select->groupBy($groupBy);
        });

        return $q;
    }

    public function getTableName()
    {
        return 'host';
    }

    public function getKeyName()
    {
        return ['id' =>  new Expression('0') ];
    }

    public function getColumns()
    {
        return [
            'name'                        => new Expression('0'),
            'hosts_down_handled'          => new Expression(
                'COALESCE(SUM(CASE WHEN host_state = 1'
                . ' AND (host_handled = \'y\' OR host_reachable = \'n\') THEN 1 ELSE 0 END),0)'
            ),
            'hosts_down_unhandled'        => new Expression(
                'COALESCE(SUM(CASE WHEN host_state = 1'
                . ' AND host_handled = \'n\' AND host_reachable = \'y\' THEN 1 ELSE 0 END),0)'
            ),
            'hosts_pending'               => new Expression(
                'COALESCE(SUM(CASE WHEN host_state = 99 THEN 1 ELSE 0 END),0)'
            ),
            'hosts_total'                 => new Expression(
                'COALESCE(SUM(CASE WHEN host_id IS NOT NULL THEN 1 ELSE 0 END),0)'
            ),
            'hosts_up'                    => new Expression(
                'COALESCE(SUM(CASE WHEN host_state = 0 THEN 1 ELSE 0 END),0)'
            ),
            'hosts_severity'              => new Expression('COALESCE(MAX(host_severity),0)'),
            'services_critical_handled'   => new Expression(
                'COALESCE(SUM(CASE WHEN service_state = 2'
                . ' AND (service_handled = \'y\' OR service_reachable = \'n\') THEN 1 ELSE 0 END),0)'
            ),
            'services_critical_unhandled' => new Expression(
                'COALESCE(SUM(CASE WHEN service_state = 2'
                . ' AND service_handled = \'n\' AND service_reachable = \'y\' THEN 1 ELSE 0 END),0)'
            ),
            'services_ok'                 => new Expression(
                'COALESCE(SUM(CASE WHEN service_state = 0 THEN 1 ELSE 0 END),0)'
            ),
            'services_pending'            => new Expression(
                'COALESCE(SUM(CASE WHEN service_state = 99 THEN 1 ELSE 0 END),0)'
            ),
            'services_total'              => new Expression(
                'COALESCE(SUM(CASE WHEN service_id IS NOT NULL THEN 1 ELSE 0 END),0)'
            ),
            'services_unknown_handled'    => new Expression(
                'COALESCE(SUM(CASE WHEN service_state = 3'
                . ' AND (service_handled = \'y\' OR service_reachable = \'n\') THEN 1 ELSE 0 END),0)'
            ),
            'services_unknown_unhandled'  => new Expression(
                'COALESCE(SUM(CASE WHEN service_state = 3'
                . ' AND service_handled = \'n\' AND service_reachable = \'y\' THEN 1 ELSE 0 END),0)'
            ),
            'services_warning_handled'    => new Expression(
                'COALESCE(SUM(CASE WHEN service_state = 1'
                . ' AND (service_handled = \'y\' OR service_reachable = \'n\') THEN 1 ELSE 0 END),0)'
            ),
            'services_warning_unhandled'  => new Expression(
                'COALESCE(SUM(CASE WHEN service_state = 1'
                . ' AND service_handled = \'n\' AND service_reachable = \'y\' THEN 1 ELSE 0 END),0)'
            )
        ];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getDefaultSort()
    {
        return null;
    }

    public function getUnions()
    {
        $unions = [
            [
                Host::class,
                [
                    'state'
                ],
                [
                    'host_id'                => 'host.id',
                    'host_state'             => 'state.soft_state',
                    'host_handled'           => 'state.is_handled',
                    'host_reachable'         => 'state.is_reachable',
                    'host_severity'          => 'state.severity',
                    'service_id'             => new Expression('NULL'),
                    'service_state'          => new Expression('NULL'),
                    'service_handled'        => new Expression('NULL'),
                    'service_reachable'      => new Expression('NULL')
                ]
            ],
            [
                Service::class,
                [
                    'environment',
                    'state'
                ],
                [
                    'host_id'                => new Expression('NULL'),
                    'host_state'             => new Expression('NULL'),
                    'host_handled'           => new Expression('NULL'),
                    'host_reachable'         => new Expression('NULL'),
                    'host_severity'          => new Expression('0'),
                    'service_id'             => 'service.id',
                    'service_state'          => 'state.soft_state',
                    'service_handled'        => 'state.is_handled',
                    'service_reachable'      => 'state.is_reachable'
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

        // This is because there is no better way
        (new Environment())->createBehaviors($behaviors);
    }

    public function createRelations(Relations $relations)
    {
        // This is because there is no better way
        (new Environment())->createRelations($relations);
    }

    public function getColumnDefinitions()
    {
       // This is because there is no better way
        return (new Environment())->getColumnDefinitions();
    }
}
