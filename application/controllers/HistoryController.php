<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\HistoryList;
use Icinga\Module\Icingadb\Web\Control\ViewModeSwitcher;
use ipl\Sql\Sql;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;
use ipl\Web\Url;

class HistoryController extends Controller
{
    public function indexAction()
    {
        $this->setTitle(t('History'));
        $compact = $this->view->compact; // TODO: Find a less-legacy way..

        $db = $this->getDb();

        $history = History::on($db)->with([
            'host',
            'host.state',
            'service',
            'service.state',
            'comment',
            'downtime',
            'flapping',
            'notification',
            'acknowledgement',
            'state'
        ]);

        $before = $this->params->shift('before', time());
        $url = Url::fromPath('icingadb/history')->setParams(clone $this->params);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($history);
        $sortControl = $this->createSortControl(
            $history,
            [
                'history.event_time desc' => t('Event Time')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);
        $searchBar = $this->createSearchBar($history, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam(),
            $viewModeSwitcher->getViewModeParam()
        ]);

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

        $history->peekAhead();

        $page = $paginationControl->getCurrentPageNumber();

        if ($page > 1 && ! $compact) {
            $history->limit($page * $limitControl->getLimit());
        }

        $history->filter(Filter::lessThanOrEqual('event_time', $before));
        $this->filter($history, $filter);
        $history->getSelectBase()
            // Make sure we'll fetch service history entries only for services which still exist
            ->where(['history.service_id IS NULL', 'history_service.id IS NOT NULL'], Sql::ANY);

        yield $this->export($history);

        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($searchBar);

        $historyList = (new HistoryList($history->execute()))
            ->setPageSize($limitControl->getLimit())
            ->setViewMode($viewModeSwitcher->getViewMode())
            ->setLoadMoreUrl($url->setParam('before', $before));
        if ($compact) {
            $historyList->setPageNumber($page);
        }

        if ($compact && $page > 1) {
            $this->document->addFrom($historyList);
        } else {
            $this->addContent($historyList);
        }

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }
    }

    public function completeAction()
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(History::class);
        $suggestions->forRequest(ServerRequest::fromGlobals());
        $this->getDocument()->add($suggestions);
    }

    public function searchEditorAction()
    {
        $editor = $this->createSearchEditor(History::on($this->getDb()), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM
        ]);

        $this->getDocument()->add($editor);
        $this->setTitle(t('Adjust Filter'));
    }
}
