<?php

namespace Icinga\Module\Eagle\Model\Behavior;

use ipl\Orm\Contract\PropertyBehavior;

class Timestamp extends PropertyBehavior
{
    public function fromDb($value, $_)
    {
        return $value / 1000.0;
    }

    public function toDb($value, $_)
    {
        return $value * 1000.0;
    }
}
