<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Common\ViewMode;
use Icinga\Module\Icingadb\Common\BaseItemList;
use ipl\Web\Url;

class HostgroupList extends BaseItemList
{
    use NoSubjectLink;
    use ViewMode;

    protected $defaultAttributes = ['class' => 'hostgroup-list item-table'];

    protected function init()
    {
        parent::init();

        $this->getAttributes()->get('class')->removeValue('item-list');
        $this->setDetailUrl(Url::fromPath('icingadb/hostgroup'));
    }

    protected function getItemClass()
    {
        $this->addAttributes(['class' => $this->getViewMode()]);

        return HostgroupListItem::class;
    }
}
