<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Query;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Connection;
use ipl\Sql\Expression;
use ipl\Sql\Select;

/**
 * Redundancy group's parent nodes summary
 *
 * @property int $objects_down_critical_handled
 * @property int $objects_down_critical_unhandled
 * @property int $objects_pending
 * @property int $objects_problems_unacknowledged
 * @property int $objects_total
 * @property int $objects_up_ok
 * @property int $objects_unknown_handled
 * @property int $objects_unknown_unhandled
 * @property int $objects_warning_handled
 * @property int $objects_warning_unhandled
 */
class RedundancyGroupParentStateSummary extends RedundancyGroup
{
    public function getSummaryColumns(): array
    {
        return [
            'objects_problem_handled' => new Expression(
                'SUM(CASE WHEN redundancy_group_from_to_host_state.soft_state = 1'
                . ' AND (redundancy_group_from_to_host_state.is_handled = \'y\''
                . ' OR redundancy_group_from_to_host_state.is_reachable = \'n\') THEN 1 ELSE 0 END'
                . ' + CASE WHEN redundancy_group_from_to_service_state.soft_state = 2'
                . ' AND (redundancy_group_from_to_service_state.is_handled = \'y\''
                . ' OR redundancy_group_from_to_service_state.is_reachable = \'n\') THEN 1 ELSE 0 END)'
            ),
            'objects_problem_unhandled' => new Expression(
                'SUM(CASE WHEN redundancy_group_from_to_host_state.soft_state = 1'
                . ' AND redundancy_group_from_to_host_state.is_handled = \'n\''
                . ' AND redundancy_group_from_to_host_state.is_reachable = \'y\' THEN 1 ELSE 0 END'
                . ' + CASE WHEN redundancy_group_from_to_service_state.soft_state = 2'
                . ' AND redundancy_group_from_to_service_state.is_handled = \'n\''
                . ' AND redundancy_group_from_to_service_state.is_reachable = \'y\' THEN 1 ELSE 0 END)'
            ),
            'objects_pending' => new Expression(
                'SUM(CASE WHEN redundancy_group_from_to_host_state.soft_state = 99 THEN 1 ELSE 0 END'
                . ' + CASE WHEN redundancy_group_from_to_service_state.soft_state = 99 THEN 1 ELSE 0 END)'
            ),
            'objects_problems_unacknowledged' => new Expression(
                'SUM(CASE WHEN redundancy_group_from_to_host_state.is_problem = \'y\''
                . ' AND redundancy_group_from_to_host_state.is_acknowledged = \'n\' THEN 1 ELSE 0 END'
                . ' + CASE WHEN redundancy_group_from_to_service_state.is_problem = \'y\''
                . ' AND redundancy_group_from_to_service_state.is_acknowledged = \'n\' THEN 1 ELSE 0 END)'
            ),
            'objects_total' => new Expression(
                'SUM(CASE WHEN redundancy_group_from_to_host.id IS NOT NULL THEN 1 ELSE 0 END)'
                . '+ SUM(CASE WHEN redundancy_group_from_to_service.id IS NOT NULL THEN 1 ELSE 0 END)'
            ),
            'objects_ok' => new Expression(
                'SUM(CASE WHEN redundancy_group_from_to_host_state.soft_state = 0 THEN 1 ELSE 0 END'
                . ' + CASE WHEN redundancy_group_from_to_service_state.soft_state = 0 THEN 1 ELSE 0 END)'
            ),
            'objects_unknown_handled' => new Expression(
                'SUM(CASE WHEN redundancy_group_from_to_service_state.soft_state = 3'
                . ' AND (redundancy_group_from_to_service_state.is_handled = \'y\''
                . ' OR redundancy_group_from_to_service_state.is_reachable = \'n\') THEN 1 ELSE 0 END)'
            ),
            'objects_unknown_unhandled' => new Expression(
                'SUM(CASE WHEN redundancy_group_from_to_service_state.soft_state = 3'
                . ' AND redundancy_group_from_to_service_state.is_handled = \'n\''
                . ' AND redundancy_group_from_to_service_state.is_reachable = \'y\' THEN 1 ELSE 0 END)'
            ),
            'objects_warning_handled' => new Expression(
                'SUM(CASE WHEN redundancy_group_from_to_service_state.soft_state = 1'
                . ' AND (redundancy_group_from_to_service_state.is_handled = \'y\''
                . ' OR redundancy_group_from_to_service_state.is_reachable = \'n\') THEN 1 ELSE 0 END)'
            ),
            'objects_warning_unhandled' => new Expression(
                'SUM(CASE WHEN redundancy_group_from_to_service_state.soft_state = 1'
                . ' AND redundancy_group_from_to_service_state.is_handled = \'n\''
                . ' AND redundancy_group_from_to_service_state.is_reachable = \'y\' THEN 1 ELSE 0 END)'
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
