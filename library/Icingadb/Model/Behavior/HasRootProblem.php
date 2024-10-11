<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model\Behavior;

use Icinga\Module\Icingadb\Model\DependencyEdge;
use ipl\Orm\AliasedExpression;
use ipl\Orm\ColumnDefinition;
use ipl\Orm\Exception\InvalidColumnException;
use ipl\Orm\Query;
use ipl\Sql\Expression;
use ipl\Stdlib\Filter;
use ipl\Orm\Contract\RewriteColumnBehavior;
use ipl\Orm\Contract\QueryAwareBehavior;

/**
 * Behavior to check if the object has a root problem
 */
class HasRootProblem implements RewriteColumnBehavior, QueryAwareBehavior
{
    /** @var Query */
    protected $query;

    public function setQuery(Query $query): self
    {
        $this->query = $query;

        return $this;
    }

    public function rewriteColumn($column, ?string $relation = null): ?AliasedExpression
    {
        if (! $this->isSelectableColumn($column)) {
            return null;
        }

        $path = 'from.';
        $subQueryRelation = $relation !== null ? $relation . $path : $path;
        $subQuery = $this->query->createSubQuery(new DependencyEdge(), $subQueryRelation, null, false)
            ->limit(1)
            ->columns([new Expression('1')]);

        $subQueryAlias = $subQuery->getResolver()->getAlias($subQuery->getModel());

        $subQuery->getSelectBase()
            ->join(
                ['to_dependency_node' => 'dependency_node'],
                ["to_dependency_node.id = $subQueryAlias.to_node_id"]
            )->joinLeft(
                ['root_dependency' => 'dependency'],
                [ "$subQueryAlias.dependency_id = root_dependency.id"]
            )->joinLeft(
                ['root_dependency_state' => 'dependency_state'],
                ['root_dependency.id = root_dependency_state.dependency_id']
            )->joinLeft(
                ['root_group' => 'redundancy_group'],
                ['root_group.id = to_dependency_node.redundancy_group_id']
            )->joinLeft(
                ['root_group_state' => 'redundancy_group_state'],
                ['root_group_state.redundancy_group_id = root_group.id']
            )->where(
                new Expression("root_dependency_state.failed = 'y' OR root_group_state.failed = 'y'")
            )->where($subQueryAlias . '_from.service_id = service.id');

        $column = $relation !== null ? str_replace('.', '_', $relation) . "_$column" : $column;

        $alias = $this->query->getDb()->quoteIdentifier([$column]);

        list($select, $values) = $this->query->getDb()
            ->getQueryBuilder()
            ->assembleSelect($subQuery->assembleSelect());

        return new AliasedExpression($alias, "($select)", null, ...$values);
    }

    public function isSelectableColumn(string $name): bool
    {
        return $name === 'has_root_problem';
    }

    public function rewriteColumnDefinition(ColumnDefinition $def, string $relation): void
    {
    }

    public function rewriteCondition(Filter\Condition $condition, $relation = null): void
    {
        $column = substr($condition->getColumn(), strlen($relation));

        if ($this->isSelectableColumn($column)) {
            throw new InvalidColumnException($column, $this->query->getModel());
        }
    }
}
