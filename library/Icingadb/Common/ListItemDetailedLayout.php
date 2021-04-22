<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use ipl\Html\BaseHtmlElement;

trait ListItemDetailedLayout
{
    protected function assembleHeader(BaseHtmlElement $header)
    {
        $header->add($this->createTitle());
        $header->add($this->createTimestamp());
    }

    protected function assembleMain(BaseHtmlElement $main)
    {
        $main->add($this->createHeader());
        $main->add($this->createCaption());
        $main->add($this->createFooter());
    }
}
