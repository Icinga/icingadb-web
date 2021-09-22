<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model\Behavior;

use ipl\Orm\Contract\RewriteBehavior;
use ipl\Stdlib\Filter;

class ReRoute implements RewriteBehavior
{
    protected $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function rewriteCondition(Filter\Condition $condition, $relation = null)
    {
        $remainingPath = $condition->metaData()->get('columnName', '');
        if (strpos($remainingPath, '.') === false) {
            return;
        }

        if (($path = $this->rewritePath($remainingPath, $relation)) !== null) {
            $class = get_class($condition);
            $filter = new $class($relation . $path, $condition->getValue());
            if ($condition->metaData()->has('forceOptimization')) {
                $filter->metaData()->set(
                    'forceOptimization',
                    $condition->metaData()->get('forceOptimization')
                );
            }

            return $filter;
        }
    }

    public function rewritePath($path, $relation = null)
    {
        $dot = strpos($path, '.');
        if ($dot !== false) {
            $routeName = substr($path, 0, $dot);
        } else {
            $routeName = $path;
        }

        if (isset($this->routes[$routeName])) {
            return $this->routes[$routeName] . ($dot !== false ? substr($path, $dot) : '');
        }
    }

    public function rewriteColumn($column, $relation = null)
    {
    }
}
