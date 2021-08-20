<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\ListItemMinimalLayout;
use ipl\Web\Widget\StateBall;

class HostListItemMinimal extends BaseHostListItem
{
    use ListItemMinimalLayout;

    protected function getStateBallSize()
    {
        return StateBall::SIZE_BIG;
    }
}
