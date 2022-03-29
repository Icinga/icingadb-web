<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model\Behavior;

use ipl\Orm\Contract\PropertyBehavior;

class Timestamp extends PropertyBehavior
{
    public function fromDb($value, $key, $_)
    {
        if ($value === null) {
            return $value;
        }

        return $value / 1000.0;
    }

    public function toDb($value, $key, $_)
    {
        if ($value === null) {
            return $value;
        }

        if (is_string($value) && ! ctype_digit($value)) {
            $timestamp = strtotime($value);
            if ($timestamp === false) {
                return $value;
            } else {
                $value = $timestamp;
            }
        }

        return $value * 1000.0;
    }
}
