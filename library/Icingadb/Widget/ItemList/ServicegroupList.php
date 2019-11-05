<?php

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Widget\BaseItemList;

class ServicegroupList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'servicegroup-list item-table'];

    protected function getItemClass()
    {
        return ServicegroupListItem::class;
    }
}
