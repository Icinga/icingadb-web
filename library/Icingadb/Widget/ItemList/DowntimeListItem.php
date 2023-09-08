<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\ListItemCommonLayout;
use ipl\Html\BaseHtmlElement;

class DowntimeListItem extends BaseDowntimeListItem
{
    use ListItemCommonLayout;

    protected function assembleMain(BaseHtmlElement $main): void
    {
        if ($this->item->is_in_effect) {
            $main->add($this->createProgress());
        }

        $main->add($this->createHeader());
        $main->add($this->createCaption());
    }
}
