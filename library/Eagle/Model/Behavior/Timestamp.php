<?php

namespace Icinga\Module\Eagle\Model\Behavior;

class Timestamp extends PropertiesBehavior
{
    public function __invoke($value, $key)
    {
        return $value / 1000.0;
    }
}
