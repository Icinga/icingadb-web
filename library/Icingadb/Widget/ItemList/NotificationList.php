<?php

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Widget\BaseItemList;

class NotificationList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'notification-list'];

    protected function getItemClass()
    {
        return NotificationListItem::class;
    }
}
