<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model\Behavior;

use ipl\Orm\Contract\PropertyBehavior;

class BoolCast extends PropertyBehavior
{
    public function fromDb($value, $key, $_)
    {
        switch ((string) $value) {
            case 'y':
                return true;
            case 'n':
                return false;
            default:
                return $value;
        }
    }

    public function toDb($value, $key, $_)
    {
        if (is_string($value)) {
            return $value;
        }

        return $value ? 'y' : 'n';
    }
}
