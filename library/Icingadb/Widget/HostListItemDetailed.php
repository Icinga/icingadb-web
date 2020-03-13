<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Compat\CompatPluginOutput;
use ipl\Html\BaseHtmlElement;

class HostListItemDetailed extends HostListItem
{
    protected function assembleCaption(BaseHtmlElement $caption)
    {
        $caption->add(CompatPluginOutput::getInstance()->render(
            $this->state->output . "\n" . $this->state->long_output
        ));
    }
}
