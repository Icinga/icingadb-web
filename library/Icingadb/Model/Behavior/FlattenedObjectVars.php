<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model\Behavior;

use ipl\Orm\Contract\RewriteFilterBehavior;
use ipl\Stdlib\Filter;

class FlattenedObjectVars implements RewriteFilterBehavior
{
    public function rewriteCondition(Filter\Condition $condition, $relation = null)
    {
        $column = $condition->metaData()->get('columnName');
        if ($column !== null && $column !== 'flatname' && $column !== 'flatvalue') {
            $nameFilter = Filter::equal($relation . 'flatname', $column);
            $class = get_class($condition);
            $valueFilter = new $class($relation . 'flatvalue', $condition->getValue());

            return Filter::all($nameFilter, $valueFilter);
        }
    }
}
