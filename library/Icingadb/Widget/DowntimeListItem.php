<?php

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\ListItemCommonLayout;
use Icinga\Module\Icingadb\Widget\ItemList\BaseDowntimeListItem;
use ipl\Html\BaseHtmlElement;

class DowntimeListItem extends BaseDowntimeListItem
{
    use ListItemCommonLayout;

    protected function assembleMain(BaseHtmlElement $main)
    {
        if ($this->item->is_in_effect) {
            $main->add($this->createProgress());
        }

        $main->add($this->createHeader());
        $main->add($this->createCaption());
    }
}
