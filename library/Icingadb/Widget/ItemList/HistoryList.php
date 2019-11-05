<?php

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Widget\BaseItemList;

class HistoryList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'history-list'];

    protected function getItemClass()
    {
        return HistoryListItem::class;
    }
}
