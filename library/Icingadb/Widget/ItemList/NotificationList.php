<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\CaptionDisabled;
use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Common\ViewMode;
use Icinga\Module\Icingadb\Common\BaseItemList;

class NotificationList extends BaseItemList
{
    use CaptionDisabled;
    use NoSubjectLink;
    use ViewMode;

    protected $defaultAttributes = ['class' => 'notification-list'];

    protected function getItemClass()
    {
        switch ($this->getViewMode()) {
            case 'minimal':
                return NotificationListItemMinimal::class;
            case 'detailed':
                return NotificationListItemDetailed::class;
            default:
                return NotificationListItem::class;
        }
    }

    protected function assemble()
    {
        $this->addAttributes(['class' => $this->getViewMode()]);

        parent::assemble();
    }
}
