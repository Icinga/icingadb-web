<?php

namespace Icinga\Module\Icingadb\Widget;

class DowntimeList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'downtime-list'];

    protected function getItemClass()
    {
        return DowntimeListItem::class;
    }
}
