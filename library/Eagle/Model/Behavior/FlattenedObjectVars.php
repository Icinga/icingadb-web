<?php

namespace Icinga\Module\Eagle\Model\Behavior;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterExpression;
use ipl\Orm\Contract\RewriteFilterBehavior;

class FlattenedObjectVars implements RewriteFilterBehavior
{
    public function rewriteCondition(FilterExpression $expression, $relation = null)
    {
        $column = $expression->getColumn();
        if ($column !== 'flatname' && $column !== 'flatvalue') {
            return Filter::matchAll(
                Filter::where($relation . 'flatname', $column),
                $expression->setColumn($relation . 'flatvalue')
            );
        }
    }
}
