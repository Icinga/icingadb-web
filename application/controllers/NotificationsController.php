<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\NotificationHistory;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\NotificationList;
use Icinga\Module\Icingadb\Widget\ShowMore;
use ipl\Sql\Sql;
use ipl\Web\Url;

class NotificationsController extends Controller
{
    public function indexAction()
    {
        $this->setTitle($this->translate('Notifications'));
        $compact = $this->view->compact;

        $db = $this->getDb();

        $notifications = NotificationHistory::on($db)->with([
            'host',
            'host.state',
            'service',
            'service.state'
        ]);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($notifications);
        $sortControl = $this->createSortControl(
            $notifications,
            [
                'notification_history.send_time desc' => $this->translate('Send Time')
            ]
        );
        $filterControl = $this->createFilterControl($notifications);

        $this->filter($notifications);
        $notifications->getSelectBase()
            // Make sure we'll fetch service history entries only for services which still exist
            ->where([
                'notification_history.service_id IS NULL',
                'notification_history_service.id IS NOT NULL'
            ], Sql::ANY);

        $notifications->peekAhead($compact);

        yield $this->export($notifications);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

        $results = $notifications->execute();

        $this->addContent(new NotificationList($results));

        if ($compact) {
            $this->addContent(
                (new ShowMore($results, Url::fromRequest()->without(['view', 'limit'])))
                    ->setAttribute('data-base-target', '_next')
                    ->setAttribute('title', sprintf(
                        $this->translate('Show all %d notifications'),
                        $notifications->count()
                    ))
            );
        }

        $this->setAutorefreshInterval(10);
    }
}
