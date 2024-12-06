<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use ipl\Html\BaseHtmlElement;

trait ListItemCommonLayout
{
    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml($this->createTitle());
        $header->add($this->createTimestamp());
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());
        $main->addHtml($this->createCaption());
    }
}
