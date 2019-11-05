<?php

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\ListItemCommonLayout;
use ipl\Web\Widget\StateBall;

class HostListItem extends BaseHostListItem
{
    use ListItemCommonLayout;

    protected function getStateBallSize()
    {
        return StateBall::SIZE_LARGE;
    }
}
