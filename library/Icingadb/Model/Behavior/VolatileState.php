<?php

namespace Icinga\Module\Icingadb\Model\Behavior;

use Icinga\Application\Config;
use Icinga\Module\Icingadb\Redis\VolatileState as RedisState;
use ipl\Orm\Contract\RetrieveBehavior;
use ipl\Orm\Model;
use Redis;

class VolatileState implements RetrieveBehavior
{
    protected $state;

    protected function getVolatileState()
    {
        if ($this->state === null) {
            // TODO(jmeyer): Use a service provider here. (Or something similar)
            $config = Config::module('icingadb')->getSection('redis');
            $redis = new Redis();
            $redis->connect(
                $config->get('host', 'redis'),
                $config->get('port', 6379)
            );

            $this->state = new RedisState($redis);
        }

        return $this->state;
    }

    public function retrieve(Model $model)
    {
        $this->getVolatileState()->fetch($model);
    }
}
