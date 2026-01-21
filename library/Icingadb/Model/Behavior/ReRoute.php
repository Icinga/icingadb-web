<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model\Behavior;

use ipl\Orm\Contract\RewriteFilterBehavior;
use ipl\Orm\Contract\RewritePathBehavior;
use ipl\Stdlib\Filter;

class ReRoute implements RewriteFilterBehavior, RewritePathBehavior
{
    protected $routes;

    /**
     * Tables with mixed object type entries for which servicegroup filters need to be resolved in multiple steps
     *
     * @var string[]
     */
    public const MIXED_TYPE_RELATIONS = ['downtime', 'comment', 'history', 'notification_history'];

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
            $chain = $condition->getChain();
            $rewrittenChain = $chain->metaData()->get('rewrittenChain');

            if (
                ! $rewrittenChain
                && in_array(substr($relation, 0, -1), self::MIXED_TYPE_RELATIONS)
                && substr($remainingPath, 0, 13) === 'servicegroup.'
            ) {
                if ($chain instanceof Filter\Any) {
                    $orgChain = Filter::any();
                } elseif ($chain instanceof Filter\All) {
                    $orgChain = Filter::all();
                } else {
                    $orgChain = Filter::none();
                }

                $nestedChain = clone $orgChain;
                $iterator = $chain->getIterator(true);

                for ($iterator->rewind(); $iterator->valid();) {
                    $rule = $iterator->current();

                    // If it's not a servicegroup filter just skip it
                    if (! $rule instanceof Filter\Condition || strpos($rule->getColumn(), 'servicegroup.') === false) {
                        $iterator->next(); // Advance iterator

                        continue;
                    }

                    $newRule = clone $rule;
                    $newRule->setColumn($relation . 'host.' . $path);

                    // This forces the filter processor to put all the rules from the "$any" chain
                    // into a single sub query instead of wrapping each of the rules into an own sub query
                    $newRule->metaData()->set('columnPath', $newRule->getColumn());
                    $newRule->metaData()->set('columnName', 'host.' . $path);
                    $newRule->metaData()->set('relationPath', $relation);

                    $chain->remove($rule); // Remove rule from the parent chain
                    $iterator->offsetUnset($iterator->key()); // Iterator is advanced automatically

                    $orgChain->add($rule); // Re-add the rule to the cloned parent chain
                    $nestedChain->add($newRule);
                }

                // A dirty hack to prevent the chain of the given condition from being traversed over again!
                $orgChain->metaData()->set('rewrittenChain', true);

                if (! $nestedChain->isEmpty()) {
                    $applyAll = Filter::all();
                    $applyAll->add(Filter::equal($relation . 'object_type', 'host'));

                    $applyAll->add($nestedChain);

                    $filter = Filter::any($orgChain, $applyAll);
                } else {
                    $filter = $orgChain;
                }
            } else {
                $class = get_class($condition);
                $filter = new $class($relation . $path, $condition->getValue());
                if ($condition->metaData()->has('forceOptimization')) {
                    $filter->metaData()->set(
                        'forceOptimization',
                        $condition->metaData()->get('forceOptimization')
                    );
                }
            }

            return $filter;
        }
    }

    public function rewritePath(string $path, ?string $relation = null): ?string
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

        return null;
    }
}
