<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Exception\NotImplementedError;
use Icinga\Module\Icingadb\Common\DetailActions;
use Icinga\Module\Icingadb\Model\DependencyNode;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\RedundancyGroup;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Model\UnreachableParent;
use Icinga\Module\Icingadb\Redis\VolatileStateResults;
use Icinga\Module\Icingadb\View\HostRenderer;
use Icinga\Module\Icingadb\View\RedundancyGroupRenderer;
use Icinga\Module\Icingadb\View\ServiceRenderer;
use Icinga\Module\Icingadb\Widget\Notice;
use InvalidArgumentException;
use ipl\Html\HtmlDocument;
use ipl\Orm\Model;
use ipl\Stdlib\Filter;
use ipl\Web\Layout\DetailedItemLayout;
use ipl\Web\Layout\ItemLayout;
use ipl\Web\Layout\MinimalItemLayout;
use ipl\Web\Widget\ItemList;
use ipl\Web\Widget\ListItem;

/**
 * ObjectList
 *
 * Create a list of icingadb objects
 *
 * @extends ItemList<RedundancyGroup> // TODO: fix type
 */
class ObjectList extends ItemList
{
    use DetailActions;

    /** @var bool Whether the list contains at least one item with an icon_image */
    protected $hasIconImages = false;

    public function __construct($data)
    {
        parent::__construct($data, function (Model $item) {
            if ($item instanceof RedundancyGroup) {
                return new RedundancyGroupRenderer();
            } elseif ($item instanceof Service) {
                return new ServiceRenderer();
            } elseif ($item instanceof Host) {
                return new HostRenderer();
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

    /**
     * @param object<UnreachableParent|DependencyNode|RedundancyGroup|Service|Host> $data
     *
     * @return ListItem
     */
    protected function createListItem(object $data)
    {
        if ($data instanceof DependencyNode) {
            if (isset($data->redundancy_group_id)) {
                $object = $data->redundancy_group;
            } else {
                $object = isset($data->service_id) ? $data->service : $data->host;
            }
        } else {
            $object = $data;
        }

        if (isset($object->icon_image->icon_image)) {
            $this->setHasIconImages(true);
        }

        $item = parent::createListItem($object);

        if ($this->getDetailActionsDisabled()) {
            return $item;
        }

        switch (true) {
            case $object instanceof RedundancyGroup:
                $this->addDetailFilterAttribute($item, Filter::equal('id', bin2hex($object->id)));

                break;
            case $object instanceof Service:
                $this->addDetailFilterAttribute(
                    $item,
                    Filter::all(
                        Filter::equal('name', $object->name),
                        Filter::equal('host.name', $object->host->name)
                    )
                );

                $this->addMultiSelectFilterAttribute(
                    $item,
                    Filter::all(
                        Filter::equal('service.name', $object->name),
                        Filter::equal('host.name', $object->host->name)
                    )
                );

                break;
            case $object instanceof Host:
                $this->addDetailFilterAttribute($item, Filter::equal('name', $object->name));
                $this->addMultiSelectFilterAttribute($item, Filter::equal('host.name', $object->name));

                break;
        }

        return $item;
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
