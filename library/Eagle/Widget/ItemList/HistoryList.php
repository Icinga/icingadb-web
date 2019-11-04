<?php

namespace Icinga\Module\Eagle\Widget\ItemList;

use Icinga\Module\Eagle\Widget\BaseItemList;

class HistoryList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'history-list'];

    protected function getItemClass()
    {
        return HistoryListItem::class;
    }
}
