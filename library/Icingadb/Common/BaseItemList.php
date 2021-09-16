<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Icinga\Module\Icingadb\Widget\EmptyState;
use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;
use ipl\Stdlib\BaseFilter;

/**
 * Base class for item lists
 */
abstract class BaseItemList extends BaseHtmlElement
{
    use BaseFilter;
    use DetailActions;

    protected $baseAttributes = [
        'class' => 'item-list',
        'data-base-target' => '_next',
        'data-pdfexport-page-breaks-at' => '.list-item'
    ];

    /** @var iterable */
    protected $data;

    /** @var bool Whether the list contains at least one item with an icon_image */
    protected $hasIconImages = false;

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

        $this->initializeDetailActions();
        $this->init();
    }

    abstract protected function getItemClass(): string;

    /**
     * Get whether the list contains at least one item with an icon_image
     *
     * @return bool
     */
    public function hasIconImages(): bool
    {
        return $this->hasIconImages;
    }

    /**
     * Set whether the list contains at least one item with an icon_image
     *
     * @param bool $hasIconImages
     */
    public function setHasIconImages(bool $hasIconImages)
    {
        $this->hasIconImages = $hasIconImages;
    }

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

        foreach ($this->data as $data) {
            /** @var BaseListItem|BaseTableRowItem|GroupGridCell $item */
            $item = new $itemClass($data, $this);

            $this->add($item);
        }

        if ($this->isEmpty()) {
            $this->setTag('div');
            $this->add(new EmptyState(t('No items found.')));
        }
    }
}
