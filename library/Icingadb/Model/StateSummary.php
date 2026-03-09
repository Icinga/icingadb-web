<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Common\Auth;
use ipl\Orm\Query;
use ipl\Orm\UnionModel;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Connection;
use ipl\Sql\Expression;
use ipl\Sql\Select;


class Statesummary extends UnionModel
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
        return "hosts_acknowledged";
    }

    public function getColumns()
    {
        return $this->getModifiedColumns(['HoststateSummary' => 'SUM', 'ServicestateSummary' => 'SUM']);
    }

    public function getSearchColumns()
    {
    }

    public function getDefaultSort()
    {
    }

    public function getModifiedColumns($config)
    {
        $modifiedColumns = [];

        foreach ($config as $model => $expression) {
            if ($model === "ServicestateSummary") {
                $columns = (new ServicestateSummary())->getSummaryColumns();
            } elseif ($model === "HoststateSummary") {
                $columns = (new HoststateSummary())->getSummaryColumns();
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
