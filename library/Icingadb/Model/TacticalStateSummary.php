<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Common\Auth;
use ipl\Orm\Query;
use ipl\Orm\Relations;
use ipl\Orm\UnionModel;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Connection;
use ipl\Sql\Expression;
use ipl\Sql\Select;

/**
 * @property string $type
 * @property ?int $hosts_acknowledged
 * @property ?int $hosts_active_checks_enabled
 * @property ?int $hosts_passive_checks_enabled
 * @property ?int $hosts_down_handled
 * @property ?int $hosts_down_unhandled
 * @property ?int $hosts_event_handler_enabled
 * @property ?int $hosts_flapping_enabled
 * @property ?int $hosts_notifications_enabled
 * @property ?int $hosts_pending
 * @property ?int $hosts_problems_unacknowledged
 * @property ?int $hosts_total
 * @property ?int $hosts_up
 * @property ?int $services_acknowledged
 * @property ?int $services_active_checks_enabled
 * @property ?int $services_passive_checks_enabled
 * @property ?int $services_critical_handled
 * @property ?int $services_critical_unhandled
 * @property ?int $services_event_handler_enabled
 * @property ?int $services_flapping_enabled
 * @property ?int $services_notifications_enabled
 * @property ?int $services_ok
 * @property ?int $services_pending
 * @property ?int $services_problems_unacknowledged
 * @property ?int $services_total
 * @property ?int $services_unknown_handled
 * @property ?int $services_unknown_unhandled
 * @property ?int $services_warning_handled
 * @property ?int $services_warning_unhandled
 */
class TacticalStateSummary extends UnionModel
{
    public static function on(Connection $db)
    {
        $q = parent::on($db);

        $q->on(
            Query::ON_SELECT_ASSEMBLED,
            function () use ($q) {
                $auth = new class () {
                    use Auth;
                };

                $auth->assertColumnRestrictions($q->getFilter());
            }
        );

        return $q;
    }

    public function getTableName()
    {
        return 'tactical_summary';
    }

    public function getKeyName()
    {
        return 'type';
    }

    public function getColumns()
    {
        return array_merge(
            array_keys((new HoststateSummary())->getSummaryColumns()),
            array_keys((new ServicestateSummary())->getSummaryColumns())
        );
    }

    public function getColumnDefinitions()
    {
        // This is because there is still no better way
        return (new ServicestateSummary())->getColumnDefinitions();
    }

    public function getDefaultSort()
    {
        return [];
    }

    public function getSearchColumns()
    {
        return ['service.name_ci', 'host.name_ci'];
    }

    public function createRelations(Relations $relations)
    {
        // This is because there is still no better way
        (new ServicestateSummary())->createRelations($relations);
        // And because of that a fake service relation is needed
        $relations->belongsTo('service', Service::class);
    }

    public function getUnions()
    {
        $hostStateSummaryColumns = array_keys((new HoststateSummary())->getSummaryColumns());
        $serviceStateSummaryColumns = array_keys((new ServicestateSummary())->getSummaryColumns());

        return [
            [
                HoststateSummary::class,
                [],
                array_merge(
                    ['type' => new Expression("'host_state'")],
                    $hostStateSummaryColumns,
                    array_combine(
                        $serviceStateSummaryColumns,
                        array_fill(0, count($serviceStateSummaryColumns), new Expression('NULL'))
                    )
                )
            ],
            [
                ServicestateSummary::class,
                [],
                array_merge(
                    ['type' => new Expression("'service_state'")],
                    array_combine(
                        $hostStateSummaryColumns,
                        array_fill(0, count($hostStateSummaryColumns), new Expression('NULL'))
                    ),
                    $serviceStateSummaryColumns
                )
            ]
        ];
    }
}
