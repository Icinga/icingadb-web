<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\NotificationHistory;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\NotificationList;

class NotificationsController extends Controller
{
    public function indexAction()
    {
        $this->setTitle($this->translate('Notifications'));

        $db = $this->getDb();

        $notifications = NotificationHistory::on($db)->with([
            'host',
            'host.state',
            'service',
            'service.host',
            'service.host.state',
            'service.state'
        ]);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($notifications);
        $sortControl = $this->createSortControl(
            $notifications,
            [
                'notification.send_time desc' => $this->translate('Send Time')
            ]
        );
        $filterControl = $this->createFilterControl($notifications);

        $this->filter($notifications);

        yield $this->export($notifications);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

        $this->addContent(new NotificationList($notifications));

        $this->setAutorefreshInterval(10);
    }
}
