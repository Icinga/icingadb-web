<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model\Behavior;

use Icinga\Data\Filter\FilterExpression;
use ipl\Orm\Contract\RewriteFilterBehavior;

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

    public function rewriteCondition(FilterExpression $expression, $relation = null)
    {
        if (! isset($expression->metaData['relationCol'])) {
            // TODO: Shouldn't be necessary. Solve this intelligently or do it elsewhere.
            return;
        }

        $column = $expression->metaData['relationCol'];
        $dot = strpos($column, '.');
        if ($dot === false) {
            return;
        }

        $leftMostRelationName = substr($column, 0, $dot);
        if (isset($this->routes[$leftMostRelationName])) {
            return new FilterExpression(
                $relation . $this->routes[$leftMostRelationName] . substr($column, $dot),
                $expression->getSign(),
                $expression->getExpression()
            );
        }
    }
}
