<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Widget\BaseItemList;

class UsergroupList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'usergroup-list item-table'];

    protected function getItemClass()
    {
        return UsergroupListItem::class;
    }
}
