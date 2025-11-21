<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Exception\NotImplementedError;
use Icinga\Module\Icingadb\Model\Comment;
use Icinga\Module\Icingadb\Model\Downtime;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Hostgroupsummary;
use Icinga\Module\Icingadb\Model\RedundancyGroup;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Model\ServicegroupSummary;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Model\Usergroup;
use Icinga\Module\Icingadb\View\CommentRenderer;
use Icinga\Module\Icingadb\View\DowntimeRenderer;
use Icinga\Module\Icingadb\View\EventRenderer;
use Icinga\Module\Icingadb\View\HostgroupRenderer;
use Icinga\Module\Icingadb\View\HostRenderer;
use Icinga\Module\Icingadb\View\RedundancyGroupRenderer;
use Icinga\Module\Icingadb\View\ServicegroupRenderer;
use Icinga\Module\Icingadb\View\ServiceRenderer;
use Icinga\Module\Icingadb\View\UsergroupRenderer;
use Icinga\Module\Icingadb\View\UserRenderer;
use ipl\Html\BaseHtmlElement;
use ipl\Orm\Model;
use ipl\Web\Layout\HeaderItemLayout;
use ipl\Web\Layout\ItemLayout;

/**
 * ObjectHeader
 *
 * Create a header for icingadb object
 *
 * @phpstan-type _PART1 = RedundancyGroup|Service|Host|Usergroup|User|Comment|Downtime|History
 * @phpstan-type _PART2 = Hostgroupsummary|ServicegroupSummary
 *
 * @template Item of _PART1|_PART2
 */
class ObjectHeader extends BaseHtmlElement
{
    protected $defaultAttributes = ['data-base-target' => '_next'];

    /** @var Item */
    protected $object;

    protected $tag = 'div';

    /**
     * Create a new object header
     *
     * @param Item $object
     */
    public function __construct(Model $object)
    {
        $this->object = $object;
    }

    /**
     * @throws NotImplementedError When the object type is not supported
     */
    protected function assemble(): void
    {
        switch (true) {
            case $this->object instanceof RedundancyGroup:
                $renderer = new RedundancyGroupRenderer();

                break;
            case $this->object instanceof Service:
                $renderer = new ServiceRenderer();

                break;
            case $this->object instanceof Host:
                $renderer = new HostRenderer();

                break;
            case $this->object instanceof Usergroup:
                $renderer = new UsergroupRenderer();

                break;
            case $this->object instanceof User:
                $renderer = new UserRenderer();

                break;
            case $this->object instanceof Comment:
                $renderer = (new CommentRenderer())->setTicketLinkDisabled();

                break;
            case $this->object instanceof Downtime:
                $renderer = (new DowntimeRenderer())->setTicketLinkDisabled();

                break;
            case $this->object instanceof History:
                $renderer = new EventRenderer();

                break;
            case $this->object instanceof Hostgroupsummary:
                $renderer = new HostgroupRenderer();

                break;
            case $this->object instanceof ServicegroupSummary:
                $renderer = new ServicegroupRenderer();

                break;
            default:
                throw new NotImplementedError('Not implemented');
        }

        $layout = new HeaderItemLayout($this->object, $renderer);

        if (isset($this->object->icon_image->icon_image)) {
            $layout->after(ItemLayout::VISUAL, 'icon-image');
        }

        $this->addAttributes($layout->getAttributes());
        $this->addHtml($layout);
    }
}
