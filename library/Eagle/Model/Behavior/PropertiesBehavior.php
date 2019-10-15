<?php

namespace Icinga\Module\Eagle\Model\Behavior;

use ipl\Orm\Contract\BehaviorInterface;
use ipl\Orm\Model;

abstract class PropertiesBehavior implements BehaviorInterface
{
    protected $properties;

    public function __construct(array $properties)
    {
        $this->properties = $properties;
    }

    public function apply(Model $model)
    {
        foreach ($this->properties as $key) {
            $model[$key] = $this($model[$key], $key);
        }
    }

    abstract public function __invoke($value, $key);
}
