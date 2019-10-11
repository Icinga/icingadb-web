<?php

namespace Icinga\Module\Eagle\Model\Behavior;

use Icinga\Application\Config;
use Icinga\Module\Eagle\Redis\VolatileState as RedisState;
use ipl\Orm\Contract\BehaviorInterface;
use ipl\Orm\Model;
use Redis;

class VolatileState implements BehaviorInterface
{
    protected $state;

    protected function getVolatileState()
    {
        if ($this->state === null) {
            // TODO(jmeyer): Use a service provider here. (Or something similar)
            $config = Config::module('eagle')->getSection('redis');
            $redis = new Redis();
            $redis->connect(
                $config->get('host', 'redis'),
                $config->get('port', 6379)
            );

            $this->state = new RedisState($redis);
        }

        return $this->state;
    }

    public function apply(Model $model)
    {
        $this->getVolatileState()->fetch($model);
    }
}
