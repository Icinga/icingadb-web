<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\ListItemCommonLayout;
use ipl\Web\Widget\StateBall;

class ServiceListItem extends BaseServiceListItem
{
    use ListItemCommonLayout;

    protected function getStateBallSize()
    {
        return StateBall::SIZE_LARGE;
    }
}
