<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Model;

use InvalidArgumentException;
use ipl\Orm\Query;
use ipl\Sql\Connection;
use ipl\Sql\Expression;
use ipl\Stdlib\Filter;

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
 * @property int $nodes_acknowledged
 * @property int $nodes_problems_unacknowledged
 */
class RedundancyGroupSummary extends DependencyNode
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
                    'service_id',
                    'service.state.soft_state',
                    'host.state.soft_state',
                ]
            ),
            'nodes_problem_handled' => new Expression(
                'SUM(CASE'
                . " WHEN %s IS NOT NULL THEN (CASE WHEN %s = 2 AND (%s = 'y' OR %s = 'n') THEN 1 ELSE 0 END)"
                . " WHEN %s = 1 AND (%s = 'y' OR %s = 'n') THEN 1"
                . ' ELSE 0'
                . ' END)',
                [
                    'service_id',
                    'service.state.soft_state',
                    'service.state.is_handled',
                    'service.state.is_reachable',
                    'host.state.soft_state',
                    'host.state.is_handled',
                    'host.state.is_reachable',
                ]
            ),
            'nodes_problem_unhandled' => new Expression(
                'SUM(CASE'
                . " WHEN %s IS NOT NULL THEN (CASE WHEN %s = 2 AND (%s = 'n' AND %s = 'y') THEN 1 ELSE 0 END)"
                . " WHEN %s = 1 AND (%s = 'n' AND %s = 'y') THEN 1"
                . ' ELSE 0'
                . ' END)',
                [
                    'service_id',
                    'service.state.soft_state',
                    'service.state.is_handled',
                    'service.state.is_reachable',
                    'host.state.soft_state',
                    'host.state.is_handled',
                    'host.state.is_reachable',
                ]
            ),
            'nodes_pending' => new Expression(
                'SUM(CASE'
                . ' WHEN %s IS NOT NULL THEN (CASE WHEN %s = 99 THEN 1 ELSE 0 END)'
                . ' WHEN %s = 99 THEN 1'
                . ' ELSE 0'
                . ' END)',
                [
                    'service_id',
                    'service.state.soft_state',
                    'host.state.soft_state',
                ]
            ),
            'nodes_unknown_handled' => new Expression(
                'SUM(CASE'
                . " WHEN %s IS NOT NULL THEN (CASE WHEN %s = 3 AND (%s = 'y' OR %s = 'n') THEN 1 ELSE 0 END)"
                . ' ELSE 0'
                . ' END)',
                [
                    'service_id',
                    'service.state.soft_state',
                    'service.state.is_handled',
                    'service.state.is_reachable'
                ]
            ),
            'nodes_unknown_unhandled' => new Expression(
                'SUM(CASE'
                . " WHEN %s IS NOT NULL THEN (CASE WHEN %s = 3 AND (%s = 'n' AND %s = 'y') THEN 1 ELSE 0 END)"
                . ' ELSE 0'
                . ' END)',
                [
                    'service_id',
                    'service.state.soft_state',
                    'service.state.is_handled',
                    'service.state.is_reachable'
                ]
            ),
            'nodes_warning_handled' => new Expression(
                'SUM(CASE'
                . " WHEN %s IS NOT NULL THEN (CASE WHEN %s = 1 AND (%s = 'y' OR %s = 'n') THEN 1 ELSE 0 END)"
                . ' ELSE 0'
                . ' END)',
                [
                    'service_id',
                    'service.state.soft_state',
                    'service.state.is_handled',
                    'service.state.is_reachable'
                ]
            ),
            'nodes_warning_unhandled' => new Expression(
                'SUM(CASE'
                . " WHEN %s IS NOT NULL THEN (CASE WHEN %s = 1 AND (%s = 'n' AND %s = 'y') THEN 1 ELSE 0 END)"
                . ' ELSE 0'
                . ' END)',
                [
                    'service_id',
                    'service.state.soft_state',
                    'service.state.is_handled',
                    'service.state.is_reachable'
                ]
            ),
            'nodes_acknowledged' => new Expression(
                'SUM(CASE'
                . " WHEN %s IS NOT NULL THEN (CASE WHEN %s = 'y' THEN 1 ELSE 0 END)"
                . " WHEN %s = 'y' THEN 1"
                . ' ELSE 0'
                . ' END)',
                [
                    'service_id',
                    'service.state.is_acknowledged',
                    'host.state.is_acknowledged',
                ]
            ),
            'nodes_problems_unacknowledged' => new Expression(
                'SUM(CASE'
                . " WHEN %s IS NOT NULL THEN (CASE WHEN %s = 'y' AND %s = 'n' THEN 1 ELSE 0 END)"
                . " WHEN %s = 'y' AND %s = 'n' THEN 1"
                . ' ELSE 0'
                . ' END)',
                [
                    'service_id',
                    'service.state.is_problem',
                    'service.state.is_acknowledged',
                    'host.state.is_problem',
                    'host.state.is_acknowledged',
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

        return $q;
    }

    /**
     * Get the summary query for the given redundancy group id
     *
     * @param string $groupId The redundancy group id for summary
     * @param Connection $db Db connection to use
     *
     * @return Query
     */
    public static function for(string $groupId, Connection $db): Query
    {
        return self::on($db)
            ->filter(Filter::equal('child.redundancy_group.id', $groupId));
    }

    public function getColumns(): array
    {
        return array_merge(parent::getColumns(), $this->getSummaryColumns());
    }

    public function getDefaultSort(): array
    {
        return [];
    }
}
