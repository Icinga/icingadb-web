<?php

namespace Icinga\Module\Icingadb\Model\Behavior;

use ipl\Orm\Contract\PropertyBehavior;

class BoolCast extends PropertyBehavior
{
    public function fromDb($value, $_)
    {
        return $value === 'y';
    }

    public function toDb($value, $_)
    {
        if (is_string($value)) {
            return $value;
        }

        return $value ? 'y' : 'n';
    }
}
