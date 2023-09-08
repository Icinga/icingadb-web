<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use ipl\Html\BaseHtmlElement;

trait ListItemMinimalLayout
{
    use CaptionDisabled;

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->add($this->createTitle());
        if (! $this->isCaptionDisabled()) {
            $header->add($this->createCaption());
        }
        $header->add($this->createTimestamp());
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->add($this->createHeader());
    }
}
