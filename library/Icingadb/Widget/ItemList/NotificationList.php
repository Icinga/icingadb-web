<?php

namespace Icinga\Module\Eagle\Widget\ItemList;

use Icinga\Module\Eagle\Widget\BaseItemList;

class NotificationList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'notification-list'];

    protected function getItemClass()
    {
        return NotificationListItem::class;
    }
}
