<?php

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Compat\CompatPluginOutput;
use ipl\Html\BaseHtmlElement;

class ServiceListItemDetailed extends ServiceListItem
{
    protected function assembleCaption(BaseHtmlElement $caption)
    {
        $caption->add(CompatPluginOutput::getInstance()->render(
            $this->state->output . "\n" . $this->state->long_output
        ));
    }
}
