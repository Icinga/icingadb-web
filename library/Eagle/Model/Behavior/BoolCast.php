<?php

namespace Icinga\Module\Eagle\Model\Behavior;

use ipl\Orm\Contract\PropertyBehavior;

class BoolCast extends PropertyBehavior
{
    public function fromDb($value, $_)
    {
        return $value === 'y';
    }

    public function toDb($value, $_)
    {
        return $value ? 'y' : 'n';
    }
}
