<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Util;

use Icinga\Application\Config;
use ipl\Orm\Query;
use ipl\Sql\Adapter\Mysql;
use ipl\Sql\Select;
use ipl\Stdlib\Str;

/**
 * Helper class to allow disabling the MySQL/MariaDB query optimizer for history queries.
 * Some versions of these RDBMS perform poorly with history queries,
 * particularly when the optimizer changes join order or uses block nested loop joins.
 * Ideally, testing across all RDBMS versions to identify when the optimizer fails and adjusting queries or
 * using optimizer switches would be preferable, but this level of effort is not justified at the moment.
 */
readonly class OptimizerHints
{
    public const DISABLE_OPTIMIZER_HINT = '/*+ NO_BNL() */ STRAIGHT_JOIN';

    /**
     * If optimizer disabling is enabled for history queries,
     * injects an optimizer hint into SELECT queries for a MySQL/MariaDB Query object,
     * forcing the RDBMS to disable the optimizer
     *
     * @param Query $q
     *
     * @return void
     */
    public static function disableOptimizerForHistoryQueries(Query $q): void
    {
        if (static::shouldDisableOptimizerForHistoryQueries() && $q->getDb()->getAdapter() instanceof Mysql) {
            // Locates the first string column, prepends the optimizer hint,
            // and resets columns to ensure it appears first in the SELECT statement:
            $q->on(Query::ON_SELECT_ASSEMBLED, static function (Select $select) {
                $columns = $select->getColumns();
                foreach ($columns as $alias => $column) {
                    if (is_string($column)) {
                        if (Str::startsWith($column, static::DISABLE_OPTIMIZER_HINT)) {
                            return;
                        }

                        unset($columns[$alias]);
                        $select->resetColumns();

                        if (is_int($alias)) {
                            array_unshift($columns, static::DISABLE_OPTIMIZER_HINT . " $column");
                        } else {
                            $columns = [$alias => static::DISABLE_OPTIMIZER_HINT . " $column"] + $columns;
                        }

                        $select->resetColumns()->columns($columns);

                        return;
                    }
                }
            });
        }
    }

    /**
     * Determines whether to disable the query optimizer for history queries.
     *
     * @return bool
     */
    protected static function shouldDisableOptimizerForHistoryQueries(): bool
    {
        return Config::module('icingadb')->get('icingadb', 'disable_optimizer_for_history_queries', false);
    }
}
