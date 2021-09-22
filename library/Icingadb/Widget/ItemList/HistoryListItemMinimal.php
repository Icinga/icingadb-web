<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\ListItemMinimalLayout;
use ipl\Web\Widget\StateBall;

class HistoryListItemMinimal extends BaseHistoryListItem
{
    use ListItemMinimalLayout;

    protected function init()
    {
        parent::init();

        if ($this->list->isCaptionDisabled()) {
            $this->setCaptionDisabled();
        }
    }

    protected function getStateBallSize(): string
    {
        return StateBall::SIZE_BIG;
    }
}
