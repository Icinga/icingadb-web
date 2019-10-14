<?php

namespace Icinga\Module\Eagle\Widget;

use Icinga\Module\Eagle\Common\ListItemCommonLayout;
use ipl\Web\Widget\StateBall;

class ServiceListItem extends BaseServiceListItem
{
    use ListItemCommonLayout;

    protected function getStateBallSize()
    {
        return StateBall::SIZE_LARGE;
    }
}
