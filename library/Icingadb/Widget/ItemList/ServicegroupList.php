<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\ViewMode;
use Icinga\Module\Icingadb\Widget\BaseItemList;

class ServicegroupList extends BaseItemList
{
    use ViewMode;

    protected $defaultAttributes = ['class' => 'servicegroup-list item-table'];

    protected function getItemClass()
    {
        $this->addAttributes(['class' => $this->getViewMode()]);

        return ServicegroupListItem::class;
    }
}
