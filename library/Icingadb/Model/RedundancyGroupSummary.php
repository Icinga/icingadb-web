<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Query;
use ipl\Sql\Connection;
use ipl\Sql\Expression;
use ipl\Sql\Select;

/**
 * Redundancy group's summary
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
            'nodes_total' => new Expression('COUNT(*)'),
            'nodes_ok' => new Expression(
                'SUM(CASE'
                . ' WHEN %s IS NOT NULL THEN (CASE WHEN %s = 0 THEN 1 ELSE 0 END)'
                . ' WHEN %s = 0 THEN 1'
                . ' ELSE 0'
                . ' END)',
                [
                    'from.to.service_id',
                    'from.to.service.state.soft_state',
                    'from.to.host.state.soft_state',
                ]
            ),
            'nodes_problem_handled' => new Expression(
                'SUM(CASE'
                . " WHEN %s IS NOT NULL THEN (CASE WHEN %s = 2 AND (%s = 'y' OR %s = 'n') THEN 1 ELSE 0 END)"
                . " WHEN %s = 1 AND (%s = 'y' OR %s = 'n') THEN 1"
                . ' ELSE 0'
                . ' END)',
                [
                    'from.to.service_id',
                    'from.to.service.state.soft_state',
                    'from.to.service.state.is_handled',
                    'from.to.service.state.is_reachable',
                    'from.to.host.state.soft_state',
                    'from.to.host.state.is_handled',
                    'from.to.host.state.is_reachable',
                ]
            ),
            'nodes_problem_unhandled' => new Expression(
                'SUM(CASE'
                . " WHEN %s IS NOT NULL THEN (CASE WHEN %s = 2 AND (%s = 'n' AND %s = 'y') THEN 1 ELSE 0 END)"
                . " WHEN %s = 1 AND (%s = 'n' AND %s = 'y') THEN 1"
                . ' ELSE 0'
                . ' END)',
                [
                    'from.to.service_id',
                    'from.to.service.state.soft_state',
                    'from.to.service.state.is_handled',
                    'from.to.service.state.is_reachable',
                    'from.to.host.state.soft_state',
                    'from.to.host.state.is_handled',
                    'from.to.host.state.is_reachable',
                ]
            ),
            'nodes_pending' => new Expression(
                'SUM(CASE'
                . ' WHEN %s IS NOT NULL THEN (CASE WHEN %s = 99 THEN 1 ELSE 0 END)'
                . ' WHEN %s = 99 THEN 1'
                . ' ELSE 0'
                . ' END)',
                [
                    'from.to.service_id',
                    'from.to.service.state.soft_state',
                    'from.to.host.state.soft_state',
                ]
            ),
            'nodes_unknown_handled' => new Expression(
                'SUM(CASE'
                . " WHEN %s IS NOT NULL THEN (CASE WHEN %s = 3 AND (%s = 'y' OR %s = 'n') THEN 1 ELSE 0 END)"
                . ' ELSE 0'
                . ' END)',
                [
                    'from.to.service_id',
                    'from.to.service.state.soft_state',
                    'from.to.service.state.is_handled',
                    'from.to.service.state.is_reachable'
                ]
            ),
            'nodes_unknown_unhandled' => new Expression(
                'SUM(CASE'
                . " WHEN %s IS NOT NULL THEN (CASE WHEN %s = 3 AND (%s = 'n' AND %s = 'y') THEN 1 ELSE 0 END)"
                . ' ELSE 0'
                . ' END)',
                [
                    'from.to.service_id',
                    'from.to.service.state.soft_state',
                    'from.to.service.state.is_handled',
                    'from.to.service.state.is_reachable'
                ]
            ),
            'nodes_warning_handled' => new Expression(
                'SUM(CASE'
                . " WHEN %s IS NOT NULL THEN (CASE WHEN %s = 1 AND (%s = 'y' OR %s = 'n') THEN 1 ELSE 0 END)"
                . ' ELSE 0'
                . ' END)',
                [
                    'from.to.service_id',
                    'from.to.service.state.soft_state',
                    'from.to.service.state.is_handled',
                    'from.to.service.state.is_reachable'
                ]
            ),
            'nodes_warning_unhandled' => new Expression(
                'SUM(CASE'
                . " WHEN %s IS NOT NULL THEN (CASE WHEN %s = 1 AND (%s = 'n' AND %s = 'y') THEN 1 ELSE 0 END)"
                . ' ELSE 0'
                . ' END)',
                [
                    'from.to.service_id',
                    'from.to.service.state.soft_state',
                    'from.to.service.state.is_handled',
                    'from.to.service.state.is_reachable'
                ]
            ),
            'nodes_acknowledged' => new Expression(
                'SUM(CASE'
                . " WHEN %s IS NOT NULL THEN (CASE WHEN %s = 'y' THEN 1 ELSE 0 END)"
                . " WHEN %s = 'y' THEN 1"
                . ' ELSE 0'
                . ' END)',
                [
                    'from.to.service_id',
                    'from.to.service.state.is_acknowledged',
                    'from.to.host.state.is_acknowledged',
                ]
            ),
            'nodes_problems_unacknowledged' => new Expression(
                'SUM(CASE'
                . " WHEN %s IS NOT NULL THEN (CASE WHEN %s = 'y' AND %s = 'n' THEN 1 ELSE 0 END)"
                . " WHEN %s = 'y' AND %s = 'n' THEN 1"
                . ' ELSE 0'
                . ' END)',
                [
                    'from.to.service_id',
                    'from.to.service.state.is_problem',
                    'from.to.service.state.is_acknowledged',
                    'from.to.host.state.is_problem',
                    'from.to.host.state.is_acknowledged',
                ]
            )
        ];
    }

    public static function on(Connection $db): Query
    {
        $q = parent::on($db);

        /** @var static $m */
        $m = $q->getModel();
        $q->columns($m->getSummaryColumns());

        $q->on($q::ON_SELECT_ASSEMBLED, function (Select $select) use ($q) {
            $model = $q->getModel();
            $groupBy = $q->getResolver()->qualifyColumnsAndAliases((array) $model->getKeyName(), $model, false);
            $select->groupBy($groupBy);
        });

        return $q;
    }

    public function getColumns(): array
    {
        return array_merge(parent::getColumns(), $this->getSummaryColumns());
    }
}
