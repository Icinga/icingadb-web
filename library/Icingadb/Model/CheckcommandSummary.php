<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;
use ipl\Orm\UnionModel;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Connection;
use ipl\Sql\Expression;
use ipl\Sql\Select;

class CheckcommandSummary extends UnionModel
{
    public static function on(Connection $db)
    {
        $q = parent::on($db);

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

    public function getTableName()
    {
        return 'checkcommand';
    }

    public function getKeyName()
    {
        return ['id' => 'checkcommand_id'];
    }

    public function getColumns()
    {
        return [
            'display_name'                => 'checkcommand_name',
            'name'                        => 'checkcommand_display_name',
            'services_critical_handled'   => new Expression(
                'SUM(CASE WHEN service_state = 2'
                . ' AND (service_handled = \'y\' OR service_reachable = \'n\') THEN 1 ELSE 0 END)'
            ),
            'services_critical_unhandled' => new Expression(
                'SUM(CASE WHEN service_state = 2'
                . ' AND service_handled = \'n\' AND service_reachable = \'y\' THEN 1 ELSE 0 END)'
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
                'SUM(CASE WHEN service_state = 3'
                . ' AND (service_handled = \'y\' OR service_reachable = \'n\') THEN 1 ELSE 0 END)'
            ),
            'services_unknown_unhandled'  => new Expression(
                'SUM(CASE WHEN service_state = 3'
                . ' AND service_handled = \'n\' AND service_reachable = \'y\' THEN 1 ELSE 0 END)'
            ),
            'services_warning_handled'    => new Expression(
                'SUM(CASE WHEN service_state = 1'
                . ' AND (service_handled = \'y\' OR service_reachable = \'n\') THEN 1 ELSE 0 END)'
            ),
            'services_warning_unhandled'  => new Expression(
                'SUM(CASE WHEN service_state = 1'
                . ' AND service_handled = \'n\' AND service_reachable = \'y\' THEN 1 ELSE 0 END)'
            ),
            'services_severity'           => new Expression('MAX(service_severity)')
        ];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getDefaultSort()
    {
        return 'name';
    }

    public function getUnions()
    {
        $unions = [
            [
                Service::class,
                [
                    'checkcommand',
                    'state'
                ],
                [
                    'checkcommand_id'           => 'checkcommand.id',
                    'checkcommand_name'         => 'checkcommand.name',
                    'checkcommand_display_name' => 'checkcommand.name',
                    'service_id'                => 'service.id',
                    'service_state'             => 'state.soft_state',
                    'service_handled'           => 'state.is_handled',
                    'service_reachable'         => 'state.is_reachable',
                    'service_severity'          => 'state.severity'
                ]
            ],
            [
                Checkcommand::class,
                [],
                [
                    'checkcommand_id'           => 'checkcommand.id',
                    'checkcommand_name'         => 'checkcommand.name',
                    'checkcommand_display_name' => 'checkcommand.name',
                    'service_id'                => new Expression('NULL'),
                    'service_state'             => new Expression('NULL'),
                    'service_handled'           => new Expression('NULL'),
                    'service_reachable'         => new Expression('NULL'),
                    'service_severity'          => new Expression('0')
                ]
            ]
        ];

        return $unions;
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id'
        ]));

        // This is because there is no better way
        (new Servicegroup())->createBehaviors($behaviors);
    }

    public function createRelations(Relations $relations)
    {
        // This is because there is no better way
        (new Servicegroup())->createRelations($relations);
    }

    public function getColumnDefinitions()
    {
        // This is because there is no better way
        return (new Servicegroup())->getColumnDefinitions();
    }
}
