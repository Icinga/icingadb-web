<?php

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\ViewMode;
use Icinga\Module\Icingadb\Widget\BaseItemList;

class HostgroupList extends BaseItemList
{
    use ViewMode;

    protected $defaultAttributes = ['class' => 'hostgroup-list item-table'];

    protected function getItemClass()
    {
        $this->addAttributes(['class' => $this->getViewMode()]);

        return HostgroupListItem::class;
    }
}
