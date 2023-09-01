<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Model\NotificationHistory;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\NotificationList;
use Icinga\Module\Icingadb\Web\Control\ViewModeSwitcher;
use ipl\Sql\Sql;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;

class NotificationsController extends Controller
{
    public function indexAction()
    {
        $this->addTitleTab(t('Notifications'));
        $compact = $this->view->compact;

        $preserveParams = [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM
        ];

        $db = $this->getDb();

        $notifications = NotificationHistory::on($db)->with([
            'history',
            'host',
            'host.state',
            'service',
            'service.state'
        ]);

        $this->handleSearchRequest($notifications);
        $before = $this->params->shift('before', time());

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($notifications);
        $sortControl = $this->createSortControl(
            $notifications,
            [
                'notification_history.send_time desc' => t('Send Time')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl, true);
        $searchBar = $this->createSearchBar($notifications, $preserveParams);

        if ($searchBar->hasBeenSent() && ! $searchBar->isValid()) {
            if ($searchBar->hasBeenSubmitted()) {
                $filter = $this->getFilter();
            } else {
                $this->addControl($searchBar);
                $this->sendMultipartUpdate();
                return;
            }
        } else {
            $filter = $searchBar->getFilter();
        }

        $notifications->peekAhead();

        $page = $paginationControl->getCurrentPageNumber();

        if ($page > 1 && ! $compact) {
            $notifications->resetOffset();
            $notifications->limit($page * $limitControl->getLimit());
        }

        $notifications->filter(Filter::lessThanOrEqual('send_time', $before));
        $this->filter($notifications, $filter);
        $notifications->filter(Filter::any(
            // Make sure we'll fetch service history entries only for services which still exist
            Filter::unlike('service_id', '*'),
            Filter::like('history.service.id', '*')
        ));

        yield $this->export($notifications);

        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($searchBar);

        $url = Url::fromRequest()
            ->onlyWith($preserveParams)
            ->setFilter($filter);

        $notificationList = (new NotificationList($notifications->execute()))
            ->setPageSize($limitControl->getLimit())
            ->setViewMode($viewModeSwitcher->getViewMode())
            ->setLoadMoreUrl($url->setParam('before', $before));

        if ($compact) {
            $notificationList->setPageNumber($page);
        }

        if ($compact && $page > 1) {
            $this->document->addFrom($notificationList);
        } else {
            $this->addContent($notificationList);
        }

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }
    }

    public function completeAction()
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(NotificationHistory::class);
        $suggestions->forRequest(ServerRequest::fromGlobals());
        $this->getDocument()->add($suggestions);
    }

    public function searchEditorAction()
    {
        $editor = $this->createSearchEditor(NotificationHistory::on($this->getDb()), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM
        ]);

        $this->getDocument()->add($editor);
        $this->setTitle(t('Adjust Filter'));
    }
}
