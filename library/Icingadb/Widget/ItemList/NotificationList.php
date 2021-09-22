<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\CaptionDisabled;
use Icinga\Module\Icingadb\Common\LoadMore;
use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Common\ViewMode;
use Icinga\Module\Icingadb\Common\BaseItemList;
use ipl\Orm\ResultSet;
use ipl\Web\Url;

class NotificationList extends BaseItemList
{
    use CaptionDisabled;
    use NoSubjectLink;
    use ViewMode;
    use LoadMore;

    protected $defaultAttributes = ['class' => 'notification-list'];

    /** @var ResultSet */
    protected $data;

    public function __construct(ResultSet $data)
    {
        parent::__construct($data);
    }

    protected function init()
    {
        $this->data = $this->getIterator($this->data);
        $this->setDetailUrl(Url::fromPath('icingadb/event'));
    }

    protected function getItemClass(): string
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
