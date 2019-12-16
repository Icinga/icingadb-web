<?php

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\CaptionDisabled;

class DowntimeList extends BaseItemList
{
    use CaptionDisabled;

    protected $defaultAttributes = ['class' => 'downtime-list'];

    protected function getItemClass()
    {
        return DowntimeListItem::class;
    }
}
