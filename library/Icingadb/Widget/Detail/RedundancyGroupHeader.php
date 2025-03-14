<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Model\RedundancyGroup;
use Icinga\Module\Icingadb\View\RedundancyGroupRenderer;
use ipl\Html\BaseHtmlElement;
use ipl\Web\Layout\HeaderItemLayout;

class RedundancyGroupHeader extends BaseHtmlElement
{
    /** @var RedundancyGroup */
    protected $group;

    protected $tag = 'div';

    public function __construct(RedundancyGroup $group)
    {
        $this->group = $group;
    }

    protected function assemble(): void
    {
        $layout = new HeaderItemLayout($this->group, new RedundancyGroupRenderer());

        $this->addAttributes($layout->getAttributes());
        $this->addHtml($layout);
    }
}
