<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Exception\NotImplementedError;
use Icinga\Module\Icingadb\Common\DetailActions;
use Icinga\Module\Icingadb\Model\Hostgroupsummary;
use Icinga\Module\Icingadb\Model\ServicegroupSummary;
use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Orm\ResultSet;
use ipl\Stdlib\Filter;
use ipl\Web\Common\ItemRenderer;
use ipl\Web\Layout\ItemLayout;
use ipl\Web\Url;
use ipl\Web\Widget\EmptyStateBar;

/**
 * ObjectGrid
 *
 * @internal The only reason this class exists is due to the detail actions. In case those are part of the ipl
 * some time, this class is obsolete, and we must be able to safely drop it.
 *
 * @template Item of Hostgroupsummary|ServicegroupSummary
 */
class ObjectGrid extends BaseHtmlElement
{
    use DetailActions;
    use Translation;

    protected $defaultAttributes = [
        'class'             => 'object-grid',
        'data-base-target'  => '_next'
    ];

    protected $tag = 'ul';

    /** @var ItemRenderer<Item> */
    protected $itemRenderer;

    /** @var ResultSet|iterable<Item> */
    protected $data;

    /** @var ?ValidHtml Message to show if the list is empty */
    protected $emptyStateMessage;

    /**
     * Create a new object grid
     *
     * @param ResultSet|iterable<Item> $data
     * @param ItemRenderer<Item> $renderer
     */
    public function __construct($data, ItemRenderer $renderer)
    {
        if (! is_iterable($data)) {
            throw new InvalidArgumentException('Data must be an array or an instance of Traversable');
        }

        $this->data = $data;
        $this->itemRenderer = $renderer;

        $this->initializeDetailActions();
    }

    /**
     * Get message to show if the list is empty
     *
     * @return ValidHtml
     */
    public function getEmptyStateMessage(): ValidHtml
    {
        if ($this->emptyStateMessage === null) {
            return new Text($this->translate('No items found.'));
        }

        return $this->emptyStateMessage;
    }

    /**
     * Set message to show if the list is empty
     *
     * @param mixed $message If empty, the default message is used
     *
     * @return $this
     */
    public function setEmptyStateMessage($message): self
    {
        if (empty($message)) {
            $this->emptyStateMessage = null;
        } else {
            $this->emptyStateMessage = Html::wantHtml($message);
        }

        return $this;
    }

    /**
     * Create a list item for the given data
     *
     * @param Item $data
     *
     * @return ValidHtml
     *
     * @throws NotImplementedError When the data is not of the expected type
     */
    protected function createListItem(object $data): ValidHtml
    {
        $layout = new ItemLayout($data, $this->itemRenderer);
        $item = new HtmlElement('li', $layout->getAttributes(), $layout);

        if ($this->getDetailActionsDisabled()) {
            return $item;
        }

        switch (true) {
            case $data instanceof Hostgroupsummary:
                $this->setDetailUrl(Url::fromPath('icingadb/hostgroup'));

                break;
            case $data instanceof ServicegroupSummary:
                $this->setDetailUrl(Url::fromPath('icingadb/servicegroup'));

                break;
            default:
                throw new NotImplementedError('Not implemented');
        }

        $this->addDetailFilterAttribute($item, Filter::equal('name', $data->name));

        return $item;
    }

    protected function assemble(): void
    {
        /** @var Item $data */
        foreach ($this->data as $data) {
            $this->addHtml($this->createListItem($data));
        }

        if ($this->isEmpty()) {
            $this->setTag('div');
            $this->addHtml(new EmptyStateBar($this->getEmptyStateMessage()));
        }
    }
}
