<?php

namespace Icinga\Module\Eagle\Widget;

use Icinga\Module\Eagle\Common\ViewMode;
use Icinga\Module\Eagle\Redis\VolatileState;
use Redis;

abstract class StateList extends BaseItemList
{
    use ViewMode;

    /** @var iterable Data source of the list */
    protected $data;

    /** @var Redis Redis connection to fetch volatile states from */
    protected $redis;

    /**
     * Set the Redis connection to fetch volatile states from
     *
     * @param Redis $redis
     *
     * @return $this
     */
    public function setRedis(Redis $redis)
    {
        $this->redis = $redis;

        return $this;
    }

    /**
     * Get the helper to fetch volatile states
     *
     * @return VolatileState
     */
    public function getVolatileState()
    {
        return new VolatileState($this->redis);
    }

    protected function assemble()
    {
        $this->addAttributes(['class' => $this->getViewMode()]);

        $itemClass = $this->getItemClass();
        $volatileState = $this->getVolatileState();

        foreach ($this->data as $object) {
            $volatileState->add($object);
            $this->add(new $itemClass($object));
        }

        $volatileState->fetch();
    }
}
