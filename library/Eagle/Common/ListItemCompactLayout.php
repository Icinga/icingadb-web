<?php

namespace Icinga\Module\Eagle\Common;

use ipl\Html\BaseHtmlElement;

trait ListItemCompactLayout
{
    protected function assembleHeader(BaseHtmlElement $header)
    {
        $header->add($this->createTitle());
        $header->add($this->createCaption());
        $header->add($this->createTimestamp());
    }

    protected function assembleMain(BaseHtmlElement $main)
    {
        $main->add($this->createHeader());
    }
}
