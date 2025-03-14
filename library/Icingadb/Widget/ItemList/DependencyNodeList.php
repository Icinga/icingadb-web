<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Exception\NotImplementedError;
use Icinga\Module\Icingadb\Common\DetailActions;
use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Model\DependencyNode;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\RedundancyGroup;
use Icinga\Module\Icingadb\Model\UnreachableParent;
use Icinga\Module\Icingadb\Redis\VolatileStateResults;
use Icinga\Module\Icingadb\View\RedundancyGroupRenderer;
use Icinga\Module\Icingadb\Widget\Notice;
use InvalidArgumentException;
use ipl\Html\HtmlDocument;
use ipl\Orm\Model;
use ipl\Web\Layout\DetailedItemLayout;
use ipl\Web\Layout\ItemLayout;
use ipl\Web\Layout\MinimalItemLayout;
use ipl\Web\Widget\ItemList;
use ipl\Web\Widget\ListItem;

/**
 * Dependency node list
 *
 * @todo This should be the new StateList class
 * @extends ItemList<RedundancyGroup>
 */
class DependencyNodeList extends ItemList
{
    use DetailActions;
    use NoSubjectLink; // TODO: Only for temporary compatibility

    protected $defaultAttributes = ['class' => ['dependency-node-list']];

    /** @var bool Whether the list contains at least one item with an icon_image */
    protected $hasIconImages = false;

    public function __construct($data)
    {
        parent::__construct($data, function (Model $item) {
            if ($item instanceof RedundancyGroup) {
                return new RedundancyGroupRenderer();
            } else {
                throw new NotImplementedError('Not implemented');
            }
        });
    }

    protected function init(): void
    {
        $this->initializeDetailActions();
    }

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
     *
     * @return $this
     */
    public function setHasIconImages(bool $hasIconImages): self
    {
        $this->hasIconImages = $hasIconImages;

        return $this;
    }

    /**
     * Set the view mode
     *
     * @param 'minimal'|'common'|'detailed' $mode
     *
     * @return $this
     */
    public function setViewMode(string $mode): self
    {
        switch ($mode) {
            case 'minimal':
                $this->setItemLayoutClass(MinimalItemLayout::class);

                break;
            case 'detailed':
                $this->setItemLayoutClass(DetailedItemLayout::class);

                break;
            case 'common':
                $this->setItemLayoutClass(ItemLayout::class);

                break;
            default:
                throw new InvalidArgumentException('Invalid view mode');
        }

        return $this;
    }

    public function getItemLayout($item): ItemLayout
    {
        $layout = parent::getItemLayout($item);
        if ($this->hasIconImages()) {
            $layout->after(ItemLayout::VISUAL, 'icon-image');
        }

        return $layout;
    }

    protected function createListItem(object $data)
    {
        /** @var UnreachableParent|DependencyNode $data */
        if ($data->redundancy_group_id !== null) {
            return (new ListItem($data->redundancy_group, $this))
                ->addAttributes(['data-action-item' => true]);
        }

        // TODO: Adjust the remaining stuff once self::getItemLayout supports host and services

        $object = $data->service_id !== null ? $data->service : $data->host;

        switch (false) {
            case MinimalItemLayout::class:
                $class = $object instanceof Host ? HostListItemMinimal::class : ServiceListItemMinimal::class;
                break;
            case DetailedItemLayout::class:
                $this->removeAttribute('class', 'default-layout');

                $class = $object instanceof Host ? HostListItemDetailed::class : ServiceListItemDetailed::class;
                break;
            default:
                $class = $object instanceof Host ? HostListItem::class : ServiceListItem::class;
        }

        return new $class($object, $this);
    }

    protected function assemble(): void
    {
        parent::assemble();

        if ($this->data instanceof VolatileStateResults && $this->data->isRedisUnavailable()) {
            $this->prependWrapper((new HtmlDocument())->addHtml(new Notice(
                t('Redis is currently unavailable. The shown information might be outdated.')
            )));
        }
    }
}
