<?php

namespace Icinga\Module\Eagle\Widget\ItemList;

use Icinga\Module\Eagle\Widget\BaseItemList;

class ServicegroupList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'servicegroup-list item-table'];

    protected function getItemClass()
    {
        return ServicegroupListItem::class;
    }
}
