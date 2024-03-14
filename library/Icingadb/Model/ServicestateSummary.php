<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Sql\Connection;
use ipl\Sql\Expression;

/**
 * @property int $services_acknowledged
 * @property int $services_active_checks_enabled
 * @property int $services_passive_checks_enabled
 * @property int $services_critical_handled
 * @property int $services_critical_unhandled
 * @property int $services_event_handler_enabled
 * @property int $services_flapping_enabled
 * @property int $services_notifications_enabled
 * @property int $services_ok
 * @property int $services_pending
 * @property int $services_problems_unacknowledged
 * @property int $services_total
 * @property int $services_unknown_handled
 * @property int $services_unknown_unhandled
 * @property int $services_warning_handled
 * @property int $services_warning_unhandled
 */
class ServicestateSummary extends Service
{
    public function getSummaryColumns()
    {
        return [
            'services_acknowledged' => new Expression(
                'SUM(CASE WHEN service_state.is_acknowledged = \'y\' THEN 1 ELSE 0 END)'
            ),
            'services_active_checks_enabled' => new Expression(
                'SUM(CASE WHEN service.active_checks_enabled = \'y\' THEN 1 ELSE 0 END)'
            ),
            'services_passive_checks_enabled' => new Expression(
                'SUM(CASE WHEN service.passive_checks_enabled = \'y\' THEN 1 ELSE 0 END)'
            ),
            'services_critical_handled' => new Expression(
                'SUM(CASE WHEN service_state.soft_state = 2'
                . ' AND (service_state.is_handled = \'y\' OR service_state.is_reachable = \'n\') THEN 1 ELSE 0 END)'
            ),
            'services_critical_unhandled' => new Expression(
                'SUM(CASE WHEN service_state.soft_state = 2'
                . ' AND service_state.is_handled = \'n\' AND service_state.is_reachable = \'y\' THEN 1 ELSE 0 END)'
            ),
            'services_event_handler_enabled' => new Expression(
                'SUM(CASE WHEN service.event_handler_enabled = \'y\' THEN 1 ELSE 0 END)'
            ),
            'services_flapping_enabled' => new Expression(
                'SUM(CASE WHEN service.flapping_enabled = \'y\' THEN 1 ELSE 0 END)'
            ),
            'services_notifications_enabled' => new Expression(
                'SUM(CASE WHEN service.notifications_enabled = \'y\' THEN 1 ELSE 0 END)'
            ),
            'services_ok' => new Expression(
                'SUM(CASE WHEN service_state.soft_state = 0 THEN 1 ELSE 0 END)'
            ),
            'services_pending' => new Expression(
                'SUM(CASE WHEN service_state.soft_state = 99 THEN 1 ELSE 0 END)'
            ),
            'services_problems_unacknowledged' => new Expression(
                'SUM(CASE WHEN service_state.is_problem = \'y\''
                . ' AND service_state.is_acknowledged = \'n\' THEN 1 ELSE 0 END)'
            ),
            'services_total' => new Expression(
                'SUM(CASE WHEN service.id IS NOT NULL THEN 1 ELSE 0 END)'
            ),
            'services_unknown_handled' => new Expression(
                'SUM(CASE WHEN service_state.soft_state = 3'
                . ' AND (service_state.is_handled = \'y\' OR service_state.is_reachable = \'n\') THEN 1 ELSE 0 END)'
            ),
            'services_unknown_unhandled' => new Expression(
                'SUM(CASE WHEN service_state.soft_state = 3'
                . ' AND service_state.is_handled = \'n\' AND service_state.is_reachable = \'y\' THEN 1 ELSE 0 END)'
            ),
            'services_warning_handled' => new Expression(
                'SUM(CASE WHEN service_state.soft_state = 1'
                . ' AND (service_state.is_handled = \'y\' OR service_state.is_reachable = \'n\') THEN 1 ELSE 0 END)'
            ),
            'services_warning_unhandled' => new Expression(
                'SUM(CASE WHEN service_state.soft_state = 1'
                . ' AND service_state.is_handled = \'n\' AND service_state.is_reachable = \'y\' THEN 1 ELSE 0 END)'
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

    public function getSearchColumns()
    {
        return ['name_ci', 'host.name_ci'];
    }
}
