<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\ListItemDetailedLayout;
use ipl\Web\Widget\StateBall;

class HistoryListItemDetailed extends BaseHistoryListItem
{
    use ListItemDetailedLayout;

    protected function getStateBallSize(): string
    {
        return StateBall::SIZE_LARGE;
    }
}
