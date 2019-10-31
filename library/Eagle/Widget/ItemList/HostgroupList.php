<?php

namespace Icinga\Module\Eagle\Widget\ItemList;

use Icinga\Module\Eagle\Widget\BaseItemList;

class HostgroupList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'hostgroup-list item-table'];

    protected function getItemClass()
    {
        return HostgroupListItem::class;
    }
}
