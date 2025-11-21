<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Exception\NotImplementedError;
use Icinga\Module\Icingadb\Common\DetailActions;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Comment;
use Icinga\Module\Icingadb\Model\DependencyNode;
use Icinga\Module\Icingadb\Model\Downtime;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\NotificationHistory;
use Icinga\Module\Icingadb\Model\RedundancyGroup;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Model\Usergroup;
use Icinga\Module\Icingadb\Redis\VolatileStateResults;
use Icinga\Module\Icingadb\View\CommentRenderer;
use Icinga\Module\Icingadb\View\DowntimeRenderer;
use Icinga\Module\Icingadb\View\HostRenderer;
use Icinga\Module\Icingadb\View\RedundancyGroupRenderer;
use Icinga\Module\Icingadb\View\ServiceRenderer;
use Icinga\Module\Icingadb\View\UsergroupRenderer;
use Icinga\Module\Icingadb\View\UserRenderer;
use Icinga\Module\Icingadb\Widget\Notice;
use InvalidArgumentException;
use ipl\Html\HtmlDocument;
use ipl\Orm\Model;
use ipl\Stdlib\Filter;
use ipl\Web\Layout\DetailedItemLayout;
use ipl\Web\Layout\ItemLayout;
use ipl\Web\Layout\MinimalItemLayout;
use ipl\Web\Url;
use ipl\Web\Widget\ItemList;

/**
 * ObjectList
 *
 * Create a list of icingadb objects
 *
 * @template Result of DependencyNode|Service|Host|Usergroup|User|Comment|Downtime
 * @template Item of RedundancyGroup|Service|Host|Usergroup|User|Comment|Downtime = Result
 *
 * @extends ItemList<Result, Item>
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
            } elseif ($item instanceof Usergroup) {
                return new UsergroupRenderer();
            } elseif ($item instanceof User) {
                return new UserRenderer();
            } elseif ($item instanceof Comment) {
                return new CommentRenderer();
            } elseif ($item instanceof Downtime) {
                return new DowntimeRenderer();
            }

            throw new NotImplementedError('Not implemented');
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

        if ($item instanceof Downtime) {
            $layout->before(ItemLayout::HEADER, 'progress');
        }

        return $layout;
    }

    protected function createListItem(object $data)
    {
        $isDependencyNodeList = false;
        if ($data instanceof DependencyNode) {
            $isDependencyNodeList = true;

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
                $this
                    ->setDetailUrl(Url::fromPath('icingadb/service'))
                    ->addDetailFilterAttribute(
                        $item,
                        Filter::all(
                            Filter::equal('name', $object->name),
                            Filter::equal('host.name', $object->host->name)
                        )
                    );

                if (! $isDependencyNodeList) {
                    $this
                        ->setMultiselectUrl(Links::servicesDetails())
                        ->addMultiSelectFilterAttribute(
                            $item,
                            Filter::all(
                                Filter::equal('service.name', $object->name),
                                Filter::equal('host.name', $object->host->name)
                            )
                        );
                }

                break;
            case $object instanceof Host:
                $this
                    ->setDetailUrl(Url::fromPath('icingadb/host'))
                    ->addDetailFilterAttribute($item, Filter::equal('name', $object->name));

                if (! $isDependencyNodeList) {
                    $this
                        ->setMultiselectUrl(Links::hostsDetails())
                        ->addMultiSelectFilterAttribute($item, Filter::equal('host.name', $object->name));
                }

                break;

            case $data instanceof User:
                $this
                    ->setDetailUrl(Url::fromPath('icingadb/contact'))
                    ->addDetailFilterAttribute($item, Filter::equal('name', $object->name));

                break;
            case $object instanceof Usergroup:
                $this
                    ->setDetailUrl(Url::fromPath('icingadb/contactgroup'))
                    ->addDetailFilterAttribute($item, Filter::equal('name', $object->name));

                break;
            case $object instanceof Comment:
                $this
                    ->setDetailUrl(Url::fromPath('icingadb/comment'))
                    ->setMultiselectUrl(Links::commentsDetails())
                    ->addDetailFilterAttribute($item, Filter::equal('name', $object->name))
                    ->addMultiSelectFilterAttribute($item, Filter::equal('comment.name', $object->name));

                break;
            case $object instanceof Downtime:
                $this
                    ->setDetailUrl(Url::fromPath('icingadb/downtime'))
                    ->setMultiselectUrl(Links::downtimesDetails())
                    ->addDetailFilterAttribute($item, Filter::equal('name', $object->name))
                    ->addMultiSelectFilterAttribute($item, Filter::equal('downtime.name', $object->name));

                break;
            case $object instanceof NotificationHistory:
                $this
                    ->setDetailUrl(Url::fromPath('icingadb/event'))
                    ->addDetailFilterAttribute($item, Filter::equal('id', bin2hex($object->history->id)));

                break;

            case $object instanceof History:
                $this
                    ->setDetailUrl(Url::fromPath('icingadb/event'))
                    ->addDetailFilterAttribute($item, Filter::equal('id', bin2hex($object->id)));

                break;
        }

        return $item;
    }

    protected function assemble(): void
    {
        parent::assemble();

        if ($this->data instanceof VolatileStateResults && $this->data->isRedisUnavailable()) {
            $this->prependWrapper((new HtmlDocument())->addHtml(new Notice(
                $this->translate('Redis is currently unavailable. The shown information might be outdated.')
            )));
        }
    }
}
