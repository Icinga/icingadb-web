<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Widget\Detail\EventHeaderUtils;
use ipl\Stdlib\Filter;
use ipl\Web\Common\BaseListItem;

abstract class BaseHistoryListItem extends BaseListItem
{
    use EventHeaderUtils;

    /** @var History */
    protected $item;

    /** @var HistoryList */
    protected $list;

    protected function init(): void
    {
        $this->setTicketLinkEnabled($this->list->getTicketLinkEnabled());
        $this->list->addDetailFilterAttribute($this, Filter::equal('id', bin2hex($this->item->id)));
    }

    protected function getObject(): History
    {
        return $this->item;
    }

    protected function wantSubjectLink(): bool
    {
        return true;
    }
}
