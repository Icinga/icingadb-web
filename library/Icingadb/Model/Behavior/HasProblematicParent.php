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
 * Behavior to check if the service has a problematic parent
 */
class HasProblematicParent implements RewriteColumnBehavior, QueryAwareBehavior
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

        $resolver = $this->query->getResolver();
        if ($relation !== null) {
            $serviceTableAlias = str_replace('.', '_', $relation);
            $column = $serviceTableAlias . "_$column";
        } else {
            $serviceTableAlias = $resolver->getAlias($this->query->getModel());
        }

        $subQueryModel = new DependencyEdge();
        $subQuery = (new Query())
            ->setDb($this->query->getDb())
            ->setModel($subQueryModel)
            ->columns([new Expression('1')])
            ->with([
                'from',
                'to',
                'dependency'
            ])
            ->limit(1)
            ->filter(Filter::any(
                Filter::equal('to.redundancy_group.state.failed', 'y'),
                Filter::equal('dependency.state.failed', 'y')
            ));

        $subQueryResolver = $subQuery->getResolver();
        $subQueryTarget = $subQueryResolver->resolveRelation($subQueryModel->getTableName() . '.from')->getTarget();
        $targetForeignKey = $subQueryResolver->qualifyColumn(
            'service_id',
            $subQueryResolver->getAlias($subQueryTarget)
        );

        $subQuery->getSelectBase()
            ->where("$targetForeignKey = {$resolver->qualifyColumn('id', $serviceTableAlias)}");

        [$select, $values] = $this->query->getDb()
            ->getQueryBuilder()
            ->assembleSelect($subQuery->assembleSelect());

        return new AliasedExpression(
            $this->query->getDb()->quoteIdentifier([$column]),
            "($select)",
            null,
            ...$values
        );
    }

    public function isSelectableColumn(string $name): bool
    {
        return $name === 'has_problematic_parent';
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
