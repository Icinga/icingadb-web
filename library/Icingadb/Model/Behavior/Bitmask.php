<?php

namespace Icinga\Module\Icingadb\Model\Behavior;

use Icinga\Data\Filter\FilterExpression;
use ipl\Orm\Contract\PropertyBehavior;
use ipl\Orm\Contract\RewriteFilterBehavior;

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

    public function rewriteCondition(FilterExpression $expression, $relation = null)
    {
        if (! isset($expression->metaData['relationCol'])) {
            // TODO: Shouldn't be necessary. Solve this intelligently or do it elsewhere.
            return;
        }

        $column = $expression->metaData['relationCol'];
        if (! isset($this->properties[$column])) {
            return;
        }

        $values = $expression->getExpression();
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

        $expression->setColumn(sprintf('%s & %s', $expression->getColumn(), $bits));
    }
}
