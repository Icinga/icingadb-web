<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use ipl\Html\BaseHtmlElement;
use ipl\Web\Widget\StateBall;

trait ListItemDetailedLayout
{
    protected function getStateBallSize(): string
    {
        return StateBall::SIZE_LARGE;
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->add($this->createTitle());
        $header->add($this->createTimestamp());
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->add($this->createHeader());
        $main->add($this->createCaption());
        $main->add($this->createFooter());
    }
}
