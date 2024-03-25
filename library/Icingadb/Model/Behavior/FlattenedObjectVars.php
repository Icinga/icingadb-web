<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model\Behavior;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Model\CustomvarFlat;
use ipl\Orm\AliasedExpression;
use ipl\Orm\ColumnDefinition;
use ipl\Orm\Contract\QueryAwareBehavior;
use ipl\Orm\Contract\RewriteColumnBehavior;
use ipl\Orm\Query;
use ipl\Stdlib\Filter;

class FlattenedObjectVars implements RewriteColumnBehavior, QueryAwareBehavior
{
    use Auth;

    /** @var Query */
    protected $query;

    public function setQuery(Query $query)
    {
        $this->query = $query;

        return $this;
    }

    public function rewriteCondition(Filter\Condition $condition, $relation = null)
    {
        $column = $condition->metaData()->get('columnName');
        if ($column !== null) {
            $relation = substr($relation, 0, -5) . 'customvar_flat.';
            $condition->metaData()
                ->set('requiresTransformation', true)
                ->set('columnPath', $relation . $column)
                ->set('relationPath', substr($relation, 0, -1));

            // The ORM's FilterProcessor only optimizes filter conditions that are in the same level (chain).
            // Previously, this behavior transformed a single condition to an ALL chain and hence the semantics
            // of the level changed, since the FilterProcessor interpreted the conditions separately from there on.
            // To not change the semantics of the condition it is required to delay the transformation of the condition
            // until the subquery is created. Though, since this is about custom variables, and such can contain dots,
            // the FilterProcessor then continues traversing the parts of the column's path, which then would include
            // the dot-separated parts of the custom variable name. To prevent this, we have to signal that what we
            // return a replacement here, that should be used as-is and not processed further.
            $condition->metaData()->set('forceResolved', true);

            // But to make it even worse: If we do that, (not transforming the condition) the FilterProcessor sees
            // multiple conditions as targeting different columns, as it doesn't know that the *columns* are in fact
            // custom variables. It then attempts to combine the conditions with an AND, which is not possible, since
            // they refer to the same columns (flatname and flatvalue) after being transformed. So we have to make
            // the condition refer to a different column, which is totally irrelevant, but since it's always the same
            // column, the FilterProcessor won't attempt to combine the conditions. The literal icing on the cake.
            $condition->setColumn('always_the_same_but_totally_irrelevant');

            return $condition;
        }
    }

    public function rewriteColumn($column, $relation = null)
    {
        $subQuery = $this->query->createSubQuery(new CustomvarFlat(), $relation)
            ->limit(1)
            ->columns('flatvalue')
            ->filter(Filter::equal('flatname', $column));

        $this->applyRestrictions($subQuery);

        $alias = $this->query->getDb()->quoteIdentifier([str_replace('.', '_', $relation) . "_$column"]);

        list($select, $values) = $this->query->getDb()->getQueryBuilder()->assembleSelect($subQuery->assembleSelect());
        return new AliasedExpression($alias, "($select)", null, ...$values);
    }

    public function rewriteColumnDefinition(ColumnDefinition $def, string $relation): void
    {
        $parts = explode('.', substr($relation, 0, -5));
        $objectType = array_pop($parts);

        $name = $def->getName();
        if (substr($name, -3) === '[*]') {
            // The suggestions also hide this from the label, so should this
            $name = substr($name, 0, -3);
        }

        // Programmatically translated since the full definition is available in class ObjectSuggestions
        $def->setLabel(sprintf(t(ucfirst($objectType) . ' %s', '..<customvar-name>'), $name));
    }

    public function isSelectableColumn(string $name): bool
    {
        return true;
    }
}
