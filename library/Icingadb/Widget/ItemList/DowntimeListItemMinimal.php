<?php

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\ListItemMinimalLayout;

class DowntimeListItemMinimal extends BaseDowntimeListItem
{
    use ListItemMinimalLayout;

    protected function init()
    {
        parent::init();

        if ($this->list->isCaptionDisabled()) {
            $this->setCaptionDisabled();
        }
    }
}
