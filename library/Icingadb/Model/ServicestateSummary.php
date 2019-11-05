<?php

namespace Icinga\Module\Icingadb\Model;

use ipl\Sql\Expression;

class ServicestateSummary extends Service
{
    public function getColumns()
    {
        return array_merge(
            parent::getColumns(),
            [

                'services_critical_handled'   => new Expression(
                    'SUM(CASE WHEN service_state.soft_state = 2 AND service_state.is_handled = \'y\' THEN 1 ELSE 0 END)'
                ),
                'services_critical_unhandled' => new Expression(
                    'SUM(CASE WHEN service_state.soft_state = 2 AND service_state.is_handled = \'n\' THEN 1 ELSE 0 END)'
                ),
                'services_ok'                 => new Expression(
                    'SUM(CASE WHEN service_state.soft_state = 0 THEN 1 ELSE 0 END)'
                ),
                'services_pending'            => new Expression(
                    'SUM(CASE WHEN service_state.soft_state = 99 THEN 1 ELSE 0 END)'
                ),
                'services_total'              => new Expression(
                    'SUM(CASE WHEN service_state.soft_state IS NOT NULL THEN 1 ELSE 0 END)'
                ),
                'services_unknown_handled'    => new Expression(
                    'SUM(CASE WHEN service_state.soft_state = 3 AND service_state.is_handled = \'y\' THEN 1 ELSE 0 END)'
                ),
                'services_unknown_unhandled'  => new Expression(
                    'SUM(CASE WHEN service_state.soft_state = 3 AND service_state.is_handled = \'n\' THEN 1 ELSE 0 END)'
                ),
                'services_warning_handled'    => new Expression(
                    'SUM(CASE WHEN service_state.soft_state = 1 AND service_state.is_handled = \'y\' THEN 1 ELSE 0 END)'
                ),
                'services_warning_unhandled'  => new Expression(
                    'SUM(CASE WHEN service_state.soft_state = 1 AND service_state.is_handled = \'n\' THEN 1 ELSE 0 END)'
                )
            ]
        );
    }
}
