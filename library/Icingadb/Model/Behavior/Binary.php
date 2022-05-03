<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model\Behavior;

use ipl\Orm\Contract\PropertyBehavior;
use ipl\Orm\Contract\QueryAwareBehavior;
use ipl\Orm\Contract\RewriteFilterBehavior;
use ipl\Orm\Query;
use ipl\Sql\Adapter\Pgsql;
use ipl\Stdlib\Filter\Condition;

use function ipl\Stdlib\get_php_type;

/**
 * Support hex filters for binary columns and PHP resource (in) / bytea hex format (out) transformation for PostgreSQL
 */
class Binary extends PropertyBehavior implements QueryAwareBehavior, RewriteFilterBehavior
{
    /**
     * Properties for {@link rewriteCondition()} to support hex filters for each adapter
     *
     * Set in {@link setQuery()} from the {@link $properties} array because the latter is reset for adapters other than
     * {@link Pgsql}, so {@link fromDb()} and {@link toDb()} don't run for them.
     *
     * @var array
     */
    protected $rewriteSubjects;

    /**
     * @throws \UnexpectedValueException If value is set and not a resource
     */
    public function fromDb($value, $key, $_)
    {
        if ($value !== null) {
            if (! is_resource($value)) {
                throw new \UnexpectedValueException(
                    sprintf('%s should be a resource got %s instead', $key, get_php_type($value))
                );
            }

            return stream_get_contents($value);
        }

        return null;
    }

    /**
     * @throws \UnexpectedValueException If value is a resource
     */
    public function toDb($value, $key, $_)
    {
        if (is_resource($value)) {
            throw new \UnexpectedValueException(sprintf('Unexpected resource for %s', $key));
        }

        if ($value === '*') {
            /**
             * Support IS (NOT) NULL filter transformation.
             * {@see \ipl\Sql\Compat\FilterProcessor::assemblePredicate()}
             */
            return $value;
        }

        /**
         * TODO(lippserd): If the filter is moved to a subquery, the value has already been processed.
         * This is because our filter processor is unfortunately doing the transformation twice at the moment.
         * {@see \ipl\Orm\Compat\FilterProcessor::requireAndResolveFilterColumns()}
         */
        if (substr($value, 0, 2) === '\\x') {
            return $value;
        }

        return sprintf('\\x%s', bin2hex($value));
    }

    public function setQuery(Query $query)
    {
        $this->rewriteSubjects = $this->properties;

        if (! $query->getDb()->getAdapter() instanceof Pgsql) {
            // Only process properties if the adapter is PostgreSQL.
            $this->properties = [];
        }
    }

    public function rewriteCondition(Condition $condition, $relation = null)
    {
        /**
         * TODO(lippserd): Duplicate code because {@link RewriteFilterBehavior}s come after {@link PropertyBehavior}s.
         * {@see \ipl\Orm\Compat\FilterProcessor::requireAndResolveFilterColumns()}
         */
        $column = $condition->metaData()->get('columnName');
        if (isset($this->rewriteSubjects[$column])) {
            $value = $condition->getValue();

            if (empty($this->properties) && is_resource($value)) {
                // Only for PostgreSQL.
                throw new \UnexpectedValueException(sprintf('Unexpected resource for %s', $column));
            }

            // ctype_xdigit expects strings.
            $value = (string) $value;
            /**
             * Although this code path is also affected by the duplicate behavior evaluation stated in {@link toDb()},
             * no further adjustments are needed as ctype_xdigit returns false for binary and bytea hex strings.
             */
            if (ctype_xdigit($value)) {
                if (empty($this->properties) && substr($value, 0, 2) !== '\\x') {
                    // Only for PostgreSQL.
                    $condition->setValue(sprintf('\\x%s', $value));
                } else {
                    $condition->setValue(hex2bin($value));
                }
            }
        }
    }
}
