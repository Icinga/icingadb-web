<?php

namespace Icinga\Module\Eagle\Widget;

use Icinga\Module\Eagle\Common\ListItemCompactLayout;
use ipl\Web\Widget\StateBall;

class HostListItemCompact extends BaseHostListItem
{
    use ListItemCompactLayout;

    protected function getStateBallSize()
    {
        return StateBall::SIZE_BIG;
    }
}
