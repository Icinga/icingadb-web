<?php

namespace Icinga\Module\Icingadb\Model;

use ipl\Sql\Expression;

class HoststateSummary extends Host
{
    public function getColumns()
    {
        return array_merge(
            parent::getColumns(),
            [
                'hosts_active_checks_enabled'  => new Expression(
                    'SUM(CASE WHEN host.active_checks_enabled = \'y\' THEN 1 ELSE 0 END)'
                ),
                'hosts_passive_checks_enabled' => new Expression(
                    'SUM(CASE WHEN host.passive_checks_enabled = \'y\' THEN 1 ELSE 0 END)'
                ),
                'hosts_down_handled'           => new Expression(
                    'SUM(CASE WHEN host_state.soft_state = 1 AND host_state.is_handled = \'y\' THEN 1 ELSE 0 END)'
                ),
                'hosts_down_unhandled'         => new Expression(
                    'SUM(CASE WHEN host_state.soft_state = 1 AND host_state.is_handled = \'n\' THEN 1 ELSE 0 END)'
                ),
                'hosts_pending'                => new Expression(
                    'SUM(CASE WHEN host_state.soft_state = 99 THEN 1 ELSE 0 END)'
                ),
                'hosts_total'                  => new Expression(
                    'SUM(CASE WHEN host_state.soft_state IS NOT NULL THEN 1 ELSE 0 END)'
                ),
                'hosts_unreachable'            => new Expression(
                    'SUM(CASE WHEN host_state.soft_state = 2 THEN 1 ELSE 0 END)'
                ),
                'hosts_unreachable_handled'    => new Expression(
                    'SUM(CASE WHEN host_state.soft_state = 2 AND host_state.is_handled = \'y\' THEN 1 ELSE 0 END)'
                ),
                'hosts_unreachable_unhandled'  => new Expression(
                    'SUM(CASE WHEN host_state.soft_state = 2 AND host_state.is_handled = \'n\' THEN 1 ELSE 0 END)'
                ),
                'hosts_up'                     => new Expression(
                    'SUM(CASE WHEN host_state.soft_state = 0 THEN 1 ELSE 0 END)'
                )
            ]
        );
    }
}
