<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Common\Auth;
use ipl\Orm\Query;
use ipl\Orm\UnionModel;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Connection;
use ipl\Sql\Expression;
use ipl\Sql\Select;


class StateSummary extends UnionModel
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

        $q->on($q::ON_SELECT_ASSEMBLED, function (Select $select) use ($q) {
            $model = $q->getModel();

            $groupBy = $q->getResolver()->qualifyColumnsAndAliases((array)$model->getKeyName(), $model, false);

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
        return "host";
    }

    public function getKeyName()
    {
        return "dummy_id";
    }

    public function getColumns()
    {
        $cols = $this->getModifiedColumns(['HoststateSummary' => 'SUM', 'ServicestateSummary' => 'SUM']);
        return $cols;
    }

    public function getSearchColumns()
    {
        return ['service.name_ci', 'host.name_ci'];
    }

    public function getDefaultSort()
    {
        return 'dummy_id DESC';
    }

    public function getModifiedColumns($config)
    {
        $modifiedColumns = [];

        foreach ($config as $model => $expression) {

            $modifiedColumns['dummy_id'] = new Expression('null');

            if ($model === "ServicestateSummary") {
                $columns = (new ServicestateSummary())->getSummaryColumns();
                if ($expression === "0") {
                    $modifiedColumns['dummy_id'] = new Expression('1');
                }
            } elseif ($model === "HoststateSummary") {
                $columns = (new HoststateSummary())->getSummaryColumns();
                if ($expression === "0") {
                    $modifiedColumns['dummy_id'] = new Expression('0');
                }
            } else {
                throw new \Exception("Unsupported Model");
            }


            foreach ($columns as $name => $value) {
                if ($expression === "0") {
                    $modifiedColumns[$name] = new Expression('0');
                } elseif ($expression === "SUM") {
                    $modifiedColumns[$name] = new Expression(
                        sprintf('SUM(%s)', $name)
                    );
                } else {
                    $modifiedColumns[$name] = $name;
                }
            }

        }

        return $modifiedColumns;
    }


    public function getUnions()
    {

        $unions = [
            [
                HoststateSummary::class,
                [

                ],
                $this->getModifiedColumns(['HoststateSummary' => 'name', 'ServicestateSummary' => '0'])
            ],
            [
                ServicestateSummary::class,
                [

                ],
                $this->getModifiedColumns(['HoststateSummary' => '0', 'ServicestateSummary' => 'name'])
            ],

        ];

        return $unions;
    }


}
