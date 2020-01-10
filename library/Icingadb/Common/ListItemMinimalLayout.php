<?php

namespace Icinga\Module\Icingadb\Common;

use ipl\Html\BaseHtmlElement;

trait ListItemMinimalLayout
{
    use CaptionDisabled;

    protected function assembleHeader(BaseHtmlElement $header)
    {
        $header->add($this->createTitle());
        if (! $this->isCaptionDisabled()) {
            $header->add($this->createCaption());
        }
        $header->add($this->createTimestamp());
    }

    protected function assembleMain(BaseHtmlElement $main)
    {
        $main->add($this->createHeader());
    }
}
