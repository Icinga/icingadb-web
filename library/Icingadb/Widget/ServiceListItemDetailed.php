<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\ListItemDetailedLayout;
use Icinga\Module\Icingadb\Compat\CompatPluginOutput;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\StateBall;

class ServiceListItemDetailed extends BaseServiceListItem
{
    use ListItemDetailedLayout;

    protected function getStateBallSize()
    {
        return StateBall::SIZE_LARGE;
    }

    protected function assembleFooter(BaseHtmlElement $footer)
    {
        $footer->add(Html::tag('p', [], 'Footer Service'));
    }

    protected function assembleCaption(BaseHtmlElement $caption)
    {
        $caption->add(CompatPluginOutput::getInstance()->render(
            $this->state->output . "\n" . $this->state->long_output
        ));
    }
}
