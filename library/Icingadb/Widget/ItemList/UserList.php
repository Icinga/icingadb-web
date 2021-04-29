<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Widget\BaseItemList;

class UserList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'user-list item-table'];

    protected function init()
    {
        parent::init();

        $this->getAttributes()->get('class')->removeValue('item-list');
    }

    protected function getItemClass()
    {
        return UserListItem::class;
    }
}
