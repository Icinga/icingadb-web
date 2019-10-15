<?php

namespace Icinga\Module\Eagle\Model\Behavior;

class BoolCast extends PropertiesBehavior
{
    public function __invoke($value, $key)
    {
        return $value === 'y';
    }
}
