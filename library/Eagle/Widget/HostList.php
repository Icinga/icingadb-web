<?php

namespace Icinga\Module\Eagle\Widget;

use Icinga\Module\Eagle\Redis\VolatileState;
use ipl\Html\BaseHtmlElement;

/**
 * Host list.
 */
class HostList extends BaseHtmlElement
{
    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'host-list', 'data-base-target' => '_next'];

    /** @var iterable Data source of the list */
    protected $hosts;

    /** @var VolatileState $volatileState Helper to fetch volatile states */
    protected $volatileState;

    /**
     * Create a new host list
     *
     * @param iterable $hosts Data source of the list
     */
    public function __construct($hosts)
    {
        if (! is_iterable($hosts)) {
            throw new \InvalidArgumentException('Data must be an array or an instance of Traversable');
        }

        $this->hosts = $hosts;
    }

    /**
     * Get the helper to fetch volatile states
     *
     * @return VolatileState
     */
    public function getVolatileState()
    {
        return $this->volatileState;
    }

    /**
     * Set the helper to fetch volatile states
     *
     * @param VolatileState $volatileState
     *
     * @return $this
     */
    public function setVolatileState(VolatileState $volatileState)
    {
        $this->volatileState = $volatileState;

        return $this;
    }

    protected function assemble()
    {
        foreach ($this->hosts as $host) {
            $this->volatileState->add($host);
            $this->add(new HostListItem($host));
        }

        $this->volatileState->fetch();
    }
}
