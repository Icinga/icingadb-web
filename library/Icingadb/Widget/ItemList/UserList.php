<?php


namespace Icinga\Module\Eagle\Widget\ItemList;

use Icinga\Module\Eagle\Widget\BaseItemList;

class UserList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'user-list item-table'];

    protected function getItemClass()
    {
        return UserListItem::class;
    }
}
