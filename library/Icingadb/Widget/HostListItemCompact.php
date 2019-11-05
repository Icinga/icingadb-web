<?php

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\ListItemCompactLayout;
use ipl\Web\Widget\StateBall;

class HostListItemCompact extends BaseHostListItem
{
    use ListItemCompactLayout;

    protected function getStateBallSize()
    {
        return StateBall::SIZE_BIG;
    }
}
