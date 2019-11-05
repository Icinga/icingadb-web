<?php

namespace Icinga\Module\Icingadb\Common;

use ipl\Html\BaseHtmlElement;

trait ListItemCommonLayout
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
    }
}
