<?php

namespace Icinga\Module\Icingadb\Model\Behavior;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterExpression;
use ipl\Orm\Contract\RewriteFilterBehavior;

class FlattenedObjectVars implements RewriteFilterBehavior
{
    public function rewriteCondition(FilterExpression $expression, $relation = null)
    {
        if (! isset($expression->metaData['relationCol'])) {
            // TODO: Shouldn't be necessary. Solve this intelligently or do it elsewhere.
            return;
        }

        $column = $expression->metaData['relationCol'];
        if ($column !== 'flatname' && $column !== 'flatvalue') {
            $nameFilter = Filter::where($relation . 'flatname', $column);
            $valueFilter = Filter::expression(
                $relation . 'flatvalue',
                $expression->getSign(),
                $expression->getExpression()
            );
            return Filter::matchAll($nameFilter, $valueFilter);
        }
    }
}
