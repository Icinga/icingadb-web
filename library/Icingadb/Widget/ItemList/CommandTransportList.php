<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\BaseOrderedItemList;
use ipl\Web\Url;

class CommandTransportList extends BaseOrderedItemList
{
    protected function init()
    {
        $this->getAttributes()->add('class', 'command-transport-list');
        $this->setDetailUrl(Url::fromPath('icingadb/command-transport/show'));
    }

    protected function getItemClass()
    {
        return CommandTransportListItem::class;
    }
}
