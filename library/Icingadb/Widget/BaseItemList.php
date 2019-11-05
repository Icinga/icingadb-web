<?php

namespace Icinga\Module\Icingadb\Widget;

use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;

/**
 * Base class for item lists
 */
abstract class BaseItemList extends BaseHtmlElement
{
    protected $baseAttributes = ['class' => 'item-list', 'data-base-target' => '_next'];

    /** @var iterable */
    protected $data;

    protected $tag = 'ul';

    /**
     * Create a new item  list
     *
     * @param iterable $data Data source of the list
     */
    public function __construct($data)
    {
        if (! is_iterable($data)) {
            throw new InvalidArgumentException('Data must be an array or an instance of Traversable');
        }

        $this->data = $data;

        $this->addAttributes($this->baseAttributes);

        $this->init();
    }

    abstract protected function getItemClass();

    /**
     * Initialize the item list
     *
     * If you want to adjust the item list after construction, override this method.
     */
    protected function init()
    {
    }

    protected function assemble()
    {
        $itemClass = $this->getItemClass();

        foreach ($this->data as $item) {
            $this->add(new $itemClass($item));
        }
    }
}
