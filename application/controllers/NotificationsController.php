<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Model\NotificationHistory;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\NotificationList;
use Icinga\Module\Icingadb\Widget\ShowMore;
use ipl\Sql\Sql;
use ipl\Web\Url;

class NotificationsController extends Controller
{
    public function indexAction()
    {
        $this->setTitle(t('Notifications'));
        $compact = $this->view->compact;

        $db = $this->getDb();

        $notifications = NotificationHistory::on($db)->with([
            'host',
            'host.state',
            'service',
            'service.state'
        ]);

        $this->handleSearchRequest($notifications);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($notifications);
        $sortControl = $this->createSortControl(
            $notifications,
            [
                'notification_history.send_time desc' => t('Send Time')
            ]
        );
        $searchBar = $this->createSearchBar($notifications, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam()
        ]);

        $this->filter($notifications, $searchBar->getFilter());
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
        $this->addControl($searchBar);

        $results = $notifications->execute();

        $this->addContent(new NotificationList($results));

        if ($compact) {
            $this->addContent(
                (new ShowMore($results, Url::fromRequest()->without(['showCompact', 'limit'])))
                    ->setAttribute('data-base-target', '_next')
                    ->setAttribute('title', sprintf(
                        t('Show all %d notifications'),
                        $notifications->count()
                    ))
            );
        }

        if ($searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }

        $this->setAutorefreshInterval(10);
    }

    public function completeAction()
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(NotificationHistory::class);
        $suggestions->forRequest(ServerRequest::fromGlobals());
        $this->getDocument()->add($suggestions);
    }
}
