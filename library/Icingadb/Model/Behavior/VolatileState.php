<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model\Behavior;

use Exception;
use Icinga\Module\Icingadb\Common\IcingaRedis;
use Icinga\Module\Icingadb\Redis\VolatileState as RedisState;
use ipl\Orm\Contract\RetrieveBehavior;
use ipl\Orm\Model;

class VolatileState implements RetrieveBehavior
{
    protected $state;

    protected function getVolatileState()
    {
        if ($this->state === null) {
            $this->state = new RedisState(IcingaRedis::instance()->getConnection());
        }

        return $this->state;
    }

    public function retrieve(Model $model)
    {
        try {
            $this->getVolatileState()->fetch($model);
        } catch (Exception $e) {
            // Pass
        }
    }
}
