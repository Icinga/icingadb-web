<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use ipl\Html\BaseHtmlElement;

trait ListItemMinimalLayout
{
    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->add($this->createTitle());
        $header->add($this->createCaption());
        $header->add($this->createTimestamp());
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->add($this->createHeader());
    }
}
