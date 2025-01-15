<?php

namespace Icinga\Module\Icingadb\Model\UnreachableParent;

use Icinga\Module\Icingadb\Redis\VolatileStateResults;
use Traversable;

class ResultSet extends VolatileStateResults
{
    protected function yieldTraversable(Traversable $traversable)
    {
        $knownIds = [];
        foreach ($traversable as $value) {
            if (isset($knownIds[$value->id])) {
                continue;
            }

            $knownIds[$value->id] = true;

            yield $value;
        }
    }
}
