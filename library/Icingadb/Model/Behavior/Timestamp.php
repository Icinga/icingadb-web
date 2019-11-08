<?php

namespace Icinga\Module\Icingadb\Model\Behavior;

use ipl\Orm\Contract\PropertyBehavior;

class Timestamp extends PropertyBehavior
{
    public function fromDb($value, $_)
    {
        return $value / 1000.0;
    }

    public function toDb($value, $_)
    {
        if (! ctype_digit($value)) {
            $value = strtotime($value);
        }

        return $value * 1000.0;
    }
}
