<?php


namespace Icinga\Module\Eagle\Widget\ItemList;

use Icinga\Module\Eagle\Widget\BaseItemList;

class UsergroupList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'usergroup-list item-table'];

    protected function getItemClass()
    {
        return UsergroupListItem::class;
    }
}
