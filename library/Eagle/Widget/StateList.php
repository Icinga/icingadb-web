<?php

namespace Icinga\Module\Eagle\Widget;

use Icinga\Module\Eagle\Common\ViewMode;
use InvalidArgumentException;
use Redis;
use Icinga\Module\Eagle\Redis\VolatileState;
use ipl\Html\BaseHtmlElement;

abstract class StateList extends BaseHtmlElement
{
    use ViewMode;

    /** @var iterable Data source of the list */
    protected $data;

    /** @var Redis Redis connection to fetch volatile states from */
    protected $redis;

    /**
     * Create a new state list
     *
     * @param iterable $data Data source of the list
     */
    public function __construct($data)
    {
        if (! is_iterable($data)) {
            throw new InvalidArgumentException('Data must be an array or an instance of Traversable');
        }

        $this->data = $data;
    }

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

    abstract protected function getItemClass();

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
