<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\ListItemMinimalLayout;
use ipl\Web\Widget\StateBall;

class RedundancyGroupListItemMinimal extends RedundancyGroupListItem
{
    use ListItemMinimalLayout;

    protected function getStateBallSize(): string
    {
        return StateBall::SIZE_BIG;
    }
}
