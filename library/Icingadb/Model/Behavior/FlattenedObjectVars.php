<?php

namespace Icinga\Module\Icingadb\Model\Behavior;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterExpression;
use ipl\Orm\Contract\RewriteFilterBehavior;

class FlattenedObjectVars implements RewriteFilterBehavior
{
    public function rewriteCondition(FilterExpression $expression, $relation = null)
    {
        $column = $expression->getColumn();
        if ($column !== 'flatname' && $column !== 'flatvalue') {
            $nameFilter = Filter::where($relation . 'flatname', $column);
            $valueFilter = Filter::where($relation . 'flatvalue', $expression->getExpression());
            $filter = Filter::matchAll($nameFilter, $valueFilter);

            // TODO: Ugly, yes, let's implement this stuff in our own filters
            $nameFilter->noOptmization = true;
            $valueFilter->noOptmization = true;
            $filter->transferMetaData = true;

            return $filter;
        }
    }
}
