<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Query;
use ipl\Sql\Connection;
use ipl\Sql\Expression;

/**
 * Redundancy group's parent nodes summary
 *
 * @property int $objects_problem_handled
 * @property int $objects_problem_unhandled
 * @property int $objects_pending
 * @property int $objects_total
 * @property int $objects_ok
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
                . '+ CASE WHEN redundancy_group_from_to_service_state.soft_state = 2'
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
                . '+ CASE WHEN redundancy_group_from_to_service_state.soft_state = 99 THEN 1 ELSE 0 END)'
            ),
            'objects_total' => new Expression(
                'SUM(CASE WHEN redundancy_group_from_to_host.id IS NOT NULL THEN 1 ELSE 0 END)'
                . '+ SUM(CASE WHEN redundancy_group_from_to_service.id IS NOT NULL THEN 1 ELSE 0 END)'
            ),
            'objects_ok' => new Expression(
                'SUM(CASE WHEN redundancy_group_from_to_host_state.soft_state = 0 THEN 1 ELSE 0 END'
                . '+ CASE WHEN redundancy_group_from_to_service_state.soft_state = 0 THEN 1 ELSE 0 END)'
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

        return $q;
    }

    public function getColumns(): array
    {
        return array_merge(parent::getColumns(), $this->getSummaryColumns());
    }
}
