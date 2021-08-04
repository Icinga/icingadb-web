<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\ListItemDetailedLayout;
use ipl\Web\Widget\StateBall;

class NotificationListItemDetailed extends BaseNotificationListItem
{
    use ListItemDetailedLayout;

    protected function getStateBallSize()
    {
        return StateBall::SIZE_LARGE;
    }
}
