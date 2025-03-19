<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Exception\NotImplementedError;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\RedundancyGroup;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\View\HostRenderer;
use Icinga\Module\Icingadb\View\RedundancyGroupRenderer;
use Icinga\Module\Icingadb\View\ServiceRenderer;
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
            default:
                throw new NotImplementedError('Not implemented');
        }

        $layout = new HeaderItemLayout($this->object, $renderer);

        $this->addAttributes($layout->getAttributes());
        $this->addHtml($layout);
    }
}
