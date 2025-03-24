<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Web\Control\ViewModeSwitcher;
use Icinga\Module\Icingadb\Widget\ItemList\LoadMoreObjectList;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;
use ipl\Web\Url;

class HistoryController extends Controller
{
    public function indexAction()
    {
        $this->addTitleTab(t('History'));
        $compact = $this->view->compact; // TODO: Find a less-legacy way..

        $preserveParams = [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM
        ];

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

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($history);
        $sortControl = $this->createSortControl(
            $history,
            [
                'history.event_time desc, history.event_type desc' => t('Event Time')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl, true);
        $searchBar = $this->createSearchBar($history, $preserveParams);

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
            $history->resetOffset();
            $history->limit($page * $limitControl->getLimit());
        }

        $history->filter(Filter::lessThanOrEqual('event_time', $before));
        $this->filter($history, $filter);

        $history->getWith()['history.host']->setJoinType('LEFT');
        $history->filter(Filter::any(
            // Because of LEFT JOINs, make sure we'll fetch history entries only for items which still exist:
            Filter::like('host.id', '*'),
            Filter::like('service.id', '*')
        ));

        yield $this->export($history);

        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($searchBar);

        $url = Url::fromRequest()
            ->onlyWith($preserveParams)
            ->setFilter($filter);

        $historyList = (new LoadMoreObjectList($history->execute()))
            ->setDetailUrl(Url::fromPath('icingadb/event'))
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
