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
            $nameFilter = Filter::like($relation . 'flatname', $column);
            $class = get_class($condition);
            $valueFilter = new $class($relation . 'flatvalue', $condition->getValue());

            return Filter::all($nameFilter, $valueFilter);
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
