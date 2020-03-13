<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\UnionModel;
use ipl\Sql\Expression;

class ServicegroupSummary extends UnionModel
{
    public function getTableName()
    {
        return 'servicegroup';
    }

    public function getKeyName()
    {
        return ['id' => 'servicegroup_id'];
    }

    public function getColumns()
    {
        return [
            'display_name'                => 'servicegroup_display_name',
            'name'                        => 'servicegroup_name',
            'services_critical_handled'   => new Expression(
                'SUM(CASE WHEN service_state = 2 AND service_handled = \'y\' THEN 1 ELSE 0 END)'
            ),
            'services_critical_unhandled' => new Expression(
                'SUM(CASE WHEN service_state = 2 AND service_handled = \'n\' THEN 1 ELSE 0 END)'
            ),
            'services_ok'                 => new Expression(
                'SUM(CASE WHEN service_state = 0 THEN 1 ELSE 0 END)'
            ),
            'services_pending'            => new Expression(
                'SUM(CASE WHEN service_state = 99 THEN 1 ELSE 0 END)'
            ),
            'services_total'              => new Expression(
                'SUM(CASE WHEN service_id IS NOT NULL THEN 1 ELSE 0 END)'
            ),
            'services_unknown_handled'    => new Expression(
                'SUM(CASE WHEN service_state = 3 AND service_handled = \'y\' THEN 1 ELSE 0 END)'
            ),
            'services_unknown_unhandled'  => new Expression(
                'SUM(CASE WHEN service_state = 3 AND service_handled = \'n\' THEN 1 ELSE 0 END)'
            ),
            'services_warning_handled'    => new Expression(
                'SUM(CASE WHEN service_state = 1 AND service_handled = \'y\' THEN 1 ELSE 0 END)'
            ),
            'services_warning_unhandled'  => new Expression(
                'SUM(CASE WHEN service_state = 1 AND service_handled = \'n\' THEN 1 ELSE 0 END)'
            ),
            'services_severity'           => new Expression('MAX(service_severity)')
        ];
    }

    public function getAggregateColumns()
    {
        return true;
    }

    public function getSearchColumns()
    {
        return ['display_name'];
    }

    public function getDefaultSort()
    {
        return 'display_name';
    }

    public function getUnions()
    {
        $unions = [
            [
                Service::class,
                [
                    'servicegroup',
                    'state'
                ],
                [
                    'servicegroup_id'           => 'servicegroup.id',
                    'servicegroup_name'         => 'servicegroup.name',
                    'servicegroup_display_name' => 'servicegroup.display_name',
                    'service_id'                => 'service.id',
                    'service_state'             => 'state.soft_state',
                    'service_handled'           => 'state.is_handled',
                    'service_severity'          => 'state.severity'
                ]
            ]
        ];

        return $unions;
    }
}
