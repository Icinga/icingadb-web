<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\CaptionDisabled;
use Icinga\Module\Icingadb\Common\LoadMore;
use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Common\ViewMode;
use Icinga\Module\Icingadb\Common\BaseItemList;

class HistoryList extends BaseItemList
{
    use CaptionDisabled;
    use NoSubjectLink;
    use ViewMode;
    use LoadMore;

    protected $defaultAttributes = ['class' => 'history-list'];

    protected function init()
    {
        $this->data = $this->getIterator($this->data);
    }

    protected function getItemClass()
    {
        switch ($this->getViewMode()) {
            case 'minimal':
                return HistoryListItemMinimal::class;
            case 'detailed':
                return HistoryListItemDetailed::class;
            default:
                return HistoryListItem::class;
        }
    }

    protected function assemble()
    {
        $this->addAttributes(['class' => $this->getViewMode()]);

        parent::assemble();
    }
}
