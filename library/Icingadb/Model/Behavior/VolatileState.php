<?php

namespace Icinga\Module\Icingadb\Model\Behavior;

use Icinga\Module\Icingadb\Common\IcingaRedis;
use Icinga\Module\Icingadb\Redis\VolatileState as RedisState;
use ipl\Orm\Contract\RetrieveBehavior;
use ipl\Orm\Model;

class VolatileState implements RetrieveBehavior
{
    use IcingaRedis;

    protected $state;

    protected function getVolatileState()
    {
        if ($this->state === null) {
            $this->state = new RedisState($this->getIcingaRedis());
        }

        return $this->state;
    }

    public function retrieve(Model $model)
    {
        $this->getVolatileState()->fetch($model);
    }
}
