<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

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
use Icinga\Module\Icingadb\View\HistoryRenderer;
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

class ObjectHeader extends BaseHtmlElement
{
    /** @var Model */ // TODO: add types
    protected $object;

    protected $tag = 'div';

    public function __construct(Model $object)
    {
        $this->object = $object;
    }

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
                $renderer = new CommentRenderer();

                break;
            case $this->object instanceof Downtime:
                $renderer = new DowntimeRenderer();

                break;
            case $this->object instanceof History:
                $renderer = new HistoryRenderer();

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

        $this->addAttributes($layout->getAttributes());
        $this->addHtml($layout);
    }
}
