<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model\Behavior;

use ipl\Orm\Contract\RewriteFilterBehavior;
use ipl\Stdlib\Filter;

class ReRoute implements RewriteFilterBehavior
{
    protected $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    public function rewriteCondition(Filter\Condition $condition, $relation = null)
    {
        $column = $condition->metaData()->get('columnName', '');
        $dot = strpos($column, '.');
        if ($dot === false) {
            return;
        }

        $leftMostRelationName = substr($column, 0, $dot);
        if (isset($this->routes[$leftMostRelationName])) {
            $class = get_class($condition);
            return new $class(
                $relation . $this->routes[$leftMostRelationName] . substr($column, $dot),
                $condition->getValue()
            );
        }
    }
}
