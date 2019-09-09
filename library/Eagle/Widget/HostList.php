<?php

namespace Icinga\Module\Eagle\Widget;

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

    /**
     * Create a new host list
     *
     * @param iterable $hosts Data source of the list
     */
    public function __construct($hosts)
    {
        if (! is_iterable($hosts)) {
            throw new \InvalidArgumentException();
        }

        $this->hosts = $hosts;
    }

    protected function assemble()
    {
        foreach ($this->hosts as $host) {
            $this->add(new HostListItem($host));
        }
    }
}
