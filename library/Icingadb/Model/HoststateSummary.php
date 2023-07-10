<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Sql\Connection;
use ipl\Sql\Expression;

class HoststateSummary extends Host
{
    public function getSummaryColumns()
    {
        return [
            'hosts_acknowledged' => new Expression(
                'SUM(CASE WHEN host_state.is_acknowledged = \'y\' THEN 1 ELSE 0 END)'
            ),
            'hosts_active_checks_enabled' => new Expression(
                'SUM(CASE WHEN host.active_checks_enabled = \'y\' THEN 1 ELSE 0 END)'
            ),
            'hosts_passive_checks_enabled' => new Expression(
                'SUM(CASE WHEN host.passive_checks_enabled = \'y\' THEN 1 ELSE 0 END)'
            ),
            'hosts_down_handled' => new Expression(
                'SUM(CASE WHEN host_state.soft_state = 1'
                . ' AND (host_state.is_handled = \'y\' OR host_state.is_reachable = \'n\') THEN 1 ELSE 0 END)'
            ),
            'hosts_down_unhandled' => new Expression(
                'SUM(CASE WHEN host_state.soft_state = 1'
                . ' AND host_state.is_handled = \'n\' AND host_state.is_reachable = \'y\' THEN 1 ELSE 0 END)'
            ),
            'hosts_event_handler_enabled' => new Expression(
                'SUM(CASE WHEN host.event_handler_enabled = \'y\' THEN 1 ELSE 0 END)'
            ),
            'hosts_flapping_enabled' => new Expression(
                'SUM(CASE WHEN host.flapping_enabled = \'y\' THEN 1 ELSE 0 END)'
            ),
            'hosts_notifications_enabled' => new Expression(
                'SUM(CASE WHEN host.notifications_enabled = \'y\' THEN 1 ELSE 0 END)'
            ),
            'hosts_pending' => new Expression(
                'SUM(CASE WHEN host_state.soft_state = 99 THEN 1 ELSE 0 END)'
            ),
            'hosts_problems_unacknowledged' => new Expression(
                'SUM(CASE WHEN host_state.is_problem = \'y\''
                . ' AND host_state.is_acknowledged = \'n\' THEN 1 ELSE 0 END)'
            ),
            'hosts_total' => new Expression(
                'SUM(CASE WHEN host.id IS NOT NULL THEN 1 ELSE 0 END)'
            ),
            'hosts_up' => new Expression(
                'SUM(CASE WHEN host_state.soft_state = 0 THEN 1 ELSE 0 END)'
            )
        ];
    }

    public static function on(Connection $db)
    {
        $q = parent::on($db);
        $q->utilize('state');

        /** @var static $m */
        $m = $q->getModel();
        $q->columns($m->getSummaryColumns());

        return $q;
    }

    public function getColumns()
    {
        return array_merge(parent::getColumns(), $this->getSummaryColumns());
    }

    public function getDefaultSort()
    {
        return null;
    }
}
