<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model\Behavior;

use ipl\Orm\Contract\PropertyBehavior;
use ipl\Orm\Contract\RewriteFilterBehavior;
use ipl\Stdlib\Filter\Condition;

/**
 * Class Bitmask
 *
 * @method void __construct(array $properties) Pass property names as keys and their bitmap ([value => bit]) as value
 */
class Bitmask extends PropertyBehavior implements RewriteFilterBehavior
{
    public function fromDb($bits, $key, $context)
    {
        $values = [];
        foreach ($context as $value => $bit) {
            if ($bits & $bit) {
                $values[] = $value;
            }
        }

        return $values;
    }

    public function toDb($value, $key, $context)
    {
        if (! is_array($value)) {
            if (is_int($value) || ctype_digit($value)) {
                return $value;
            }

            return isset($context[$value]) ? $context[$value] : -1;
        }

        $bits = [];
        $allBits = 0;
        foreach ($value as $v) {
            if (isset($context[$v])) {
                $bits[] = $context[$v];
                $allBits |= $context[$v];
            } elseif (is_int($v) || ctype_digit($v)) {
                $bits[] = $v;
                $allBits |= $v;
            }
        }

        $bits[] = $allBits;
        return $bits;
    }

    public function rewriteCondition(Condition $condition, $relation = null)
    {
        $column = $condition->metaData()->get('columnName');
        if (! isset($this->properties[$column])) {
            return;
        }

        $values = $condition->getValue();
        if (! is_array($values)) {
            if (ctype_digit($values)) {
                return;
            }

            $values = [$values];
        }

        $bits = 0;
        foreach ($values as $value) {
            if (isset($this->properties[$column][$value])) {
                $bits |= $this->properties[$column][$value];
            } elseif (is_int($value) || ctype_digit($value)) {
                $bits |= $value;
            }
        }

        $condition->setColumn(sprintf('%s & %s', $condition->getColumn(), $bits));
    }
}
