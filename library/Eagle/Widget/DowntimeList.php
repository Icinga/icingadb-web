<?php

namespace Icinga\Module\Eagle\Widget;

class DowntimeList extends BaseItemList
{
    protected function getItemClass()
    {
        return DowntimeListItem::class;
    }
}
