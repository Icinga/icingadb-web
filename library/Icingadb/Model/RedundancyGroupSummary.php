<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Query;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Connection;
use ipl\Sql\Expression;
use ipl\Sql\Select;

/**
 * Redundancy group's summary (The nodes could only host and service)
 *
 * @property int $nodes_total
 * @property int $nodes_ok
 * @property int $nodes_problem_handled
 * @property int $nodes_problem_unhandled
 * @property int $nodes_pending
 * @property int $nodes_unknown_handled
 * @property int $nodes_unknown_unhandled
 * @property int $nodes_warning_handled
 * @property int $nodes_warning_unhandled
 */
class RedundancyGroupSummary extends RedundancyGroup
{
    public function getSummaryColumns(): array
    {
        return [
            'nodes_total' => new Expression(
                'SUM(CASE'
                . ' WHEN %s IS NOT NULL THEN 1'
                . ' WHEN %s IS NOT NULL THEN 1'
                . ' ELSE 0 END)',
                [
                    'from.to.service_id',
                    'from.to.host_id',
                ]
            ),
            'nodes_ok' => new Expression(
                'SUM(CASE'
                . ' WHEN %s IS NOT NULL THEN (CASE WHEN %s = 0 THEN 1 ELSE 0 END)'
                . ' WHEN %s IS NOT NULL THEN (CASE WHEN %s = 0 THEN 1 ELSE 0 END)'
                . ' ELSE 0 END)',
                [
                    'from.to.service.id',
                    'from.to.service.state.soft_state',
                    'from.to.host_id',
                    'from.to.host.state.soft_state',
                ]
            ),
            'nodes_problem_handled' => new Expression(
                'SUM(CASE'
                . ' WHEN %s IS NOT NULL THEN (CASE WHEN %s = 2 AND (%s = \'y\' OR %s = \'n\') THEN 1 ELSE 0 END)'
                . ' WHEN %s IS NOT NULL THEN (CASE WHEN %s = 1 AND (%s = \'y\' OR %s = \'n\') THEN 1 ELSE 0 END)'
                . ' ELSE 0 END)',
                [
                    'from.to.service_id',
                    'from.to.service.state.soft_state',
                    'from.to.service.state.is_handled',
                    'from.to.service.state.is_reachable',
                    'from.to.host_id',
                    'from.to.host.state.soft_state',
                    'from.to.host.state.is_handled',
                    'from.to.host.state.is_reachable',
                ]
            ),
            'nodes_problem_unhandled' => new Expression(
                'SUM(CASE'
                . ' WHEN %s IS NOT NULL THEN (CASE WHEN %s = 2 AND (%s = \'n\' AND %s = \'y\') THEN 1 ELSE 0 END)'
                . ' WHEN %s IS NOT NULL THEN (CASE WHEN %s = 1 AND (%s = \'n\' AND %s = \'y\') THEN 1 ELSE 0 END)'
                . ' ELSE 0 END)',
                [
                    'from.to.service_id',
                    'from.to.service.state.soft_state',
                    'from.to.service.state.is_handled',
                    'from.to.service.state.is_reachable',
                    'from.to.host_id',
                    'from.to.host.state.soft_state',
                    'from.to.host.state.is_handled',
                    'from.to.host.state.is_reachable',
                ]
            ),
            'nodes_pending' => new Expression(
                'SUM(CASE'
                . ' WHEN %s IS NOT NULL THEN (CASE WHEN %s = 99 THEN 1 ELSE 0 END)'
                . ' WHEN %s IS NOT NULL THEN (CASE WHEN %s = 99 THEN 1 ELSE 0 END)'
                . ' ELSE 0 END)',
                [
                    'from.to.service.id',
                    'from.to.service.state.soft_state',
                    'from.to.host_id',
                    'from.to.host.state.soft_state',
                ]
            ),
            'nodes_unknown_handled' => new Expression(
                'SUM(CASE'
                . ' WHEN %s IS NOT NULL THEN (CASE WHEN %s = 3 AND (%s = \'y\' OR %s = \'n\') THEN 1 ELSE 0 END)'
                . ' ELSE 0 END)',
                [
                    'from.to.service_id',
                    'from.to.service.state.soft_state',
                    'from.to.service.state.is_handled',
                    'from.to.service.state.is_reachable'
                ]
            ),
            'nodes_unknown_unhandled' => new Expression(
                'SUM(CASE'
                . ' WHEN %s IS NOT NULL THEN (CASE WHEN %s = 3 AND (%s = \'n\' AND %s = \'y\') THEN 1 ELSE 0 END)'
                . ' ELSE 0 END)',
                [
                    'from.to.service_id',
                    'from.to.service.state.soft_state',
                    'from.to.service.state.is_handled',
                    'from.to.service.state.is_reachable'
                ]
            ),
            'nodes_warning_handled' => new Expression(
                'SUM(CASE'
                . ' WHEN %s IS NOT NULL THEN (CASE WHEN %s = 1 AND (%s = \'y\' OR %s = \'n\') THEN 1 ELSE 0 END)'
                . ' ELSE 0 END)',
                [
                    'from.to.service_id',
                    'from.to.service.state.soft_state',
                    'from.to.service.state.is_handled',
                    'from.to.service.state.is_reachable'
                ]
            ),
            'nodes_warning_unhandled' => new Expression(
                'SUM(CASE'
                . ' WHEN %s IS NOT NULL THEN (CASE WHEN %s = 1 AND (%s = \'n\' AND %s = \'y\') THEN 1 ELSE 0 END)'
                . ' ELSE 0 END)',
                [
                    'from.to.service_id',
                    'from.to.service.state.soft_state',
                    'from.to.service.state.is_handled',
                    'from.to.service.state.is_reachable'
                ]
            )
        ];
    }

    public static function on(Connection $db): Query
    {
        $q = parent::on($db)->with([
            'from',
            'from.to.host',
            'from.to.host.state',
            'from.to.service',
            'from.to.service.state'
        ]);

        /** @var static $m */
        $m = $q->getModel();
        $q->columns($m->getSummaryColumns());

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

    public function getColumns(): array
    {
        return array_merge(parent::getColumns(), $this->getSummaryColumns());
    }
}
