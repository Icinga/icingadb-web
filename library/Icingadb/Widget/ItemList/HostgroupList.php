<?php

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Widget\BaseItemList;

class HostgroupList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'hostgroup-list item-table'];

    protected function getItemClass()
    {
        return HostgroupListItem::class;
    }
}
