<?php

namespace Icinga\Module\Eagle\Widget;

use Icinga\Module\Eagle\Common\ListItemCompactLayout;
use ipl\Web\Widget\StateBall;

class ServiceListItemCompact extends BaseServiceListItem
{
    use ListItemCompactLayout;

    protected function getStateBallSize()
    {
        return StateBall::SIZE_BIG;
    }
}
