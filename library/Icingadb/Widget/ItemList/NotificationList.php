<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\DetailActions;
use Icinga\Module\Icingadb\Common\LoadMore;
use Icinga\Module\Icingadb\Common\ViewMode;
use ipl\Orm\ResultSet;
use ipl\Web\Common\BaseItemList;
use ipl\Web\Url;

class NotificationList extends BaseItemList
{
    use ViewMode;
    use LoadMore;
    use DetailActions;

    protected $defaultAttributes = ['class' => 'notification-list'];

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
                return NotificationListItemMinimal::class;
            case 'detailed':
                $this->removeAttribute('class', 'default-layout');

                return NotificationListItemDetailed::class;
            default:
                return NotificationListItem::class;
        }
    }

    protected function assemble(): void
    {
        $this->addAttributes(['class' => $this->getViewMode()]);

        parent::assemble();
    }
}
