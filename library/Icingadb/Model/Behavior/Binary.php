<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model\Behavior;

use ipl\Orm\Contract\PropertyBehavior;
use ipl\Orm\Contract\QueryAwareBehavior;
use ipl\Orm\Query;
use ipl\Sql\Adapter\Pgsql;

use function ipl\Stdlib\get_php_type;

class Binary extends PropertyBehavior implements QueryAwareBehavior
{
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

    public function toDb($value, $key, $_)
    {
        if (is_resource($value)) {
            throw new \UnexpectedValueException(sprintf('Unexpected resource for %s', $key));
        }

        return sprintf('\\x%s', bin2hex($value));
    }

    public function setQuery(Query $query)
    {
        if (! $query->getDb()->getAdapter() instanceof Pgsql) {
            // Only process properties if the adapter is PostgreSQL.
            $this->properties = [];
        }
    }
}
