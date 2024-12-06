<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\DetailActions;
use Icinga\Module\Icingadb\Common\LoadMore;
use Icinga\Module\Icingadb\Common\TicketLinks;
use Icinga\Module\Icingadb\Common\ViewMode;
use ipl\Orm\ResultSet;
use ipl\Web\Common\BaseItemList;
use ipl\Web\Url;

class HistoryList extends BaseItemList
{
    use ViewMode;
    use LoadMore;
    use TicketLinks;
    use DetailActions;

    protected $defaultAttributes = ['class' => 'history-list'];

    protected function init(): void
    {
        /** @var ResultSet $data */
        $data = $this->data;
        $this->data = $this->getIterator($data);
        $this->initializeDetailActions();
        $this->setDetailUrl(Url::fromPath('icingadb/event'));
    }

    protected function getItemClass(): string
    {
        switch ($this->getViewMode()) {
            case 'minimal':
                return HistoryListItemMinimal::class;
            case 'detailed':
                $this->removeAttribute('class', 'default-layout');

                return HistoryListItemDetailed::class;
            default:
                return HistoryListItem::class;
        }
    }

    protected function assemble(): void
    {
        $this->addAttributes(['class' => $this->getViewMode()]);

        parent::assemble();
    }
}
