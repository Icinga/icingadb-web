<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\HistoryList;
use Icinga\Module\Icingadb\Widget\ShowMore;
use ipl\Sql\Sql;
use ipl\Web\Filter\QueryString;
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

        $url = Url::fromPath('icingadb/history')->setParams(clone $this->params);
        if (! $this->params->has('page') || ($page = (int) $this->params->shift('page')) < 1) {
            $page = 1;
        }

        $limitControl = $this->createLimitControl();
        $sortControl = $this->createSortControl(
            $history,
            [
                'history.event_time desc' => t('Event Time')
            ]
        );
        $searchBar = $this->createSearchBar($history, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam()
        ]);

        if ($searchBar->hasBeenSent() && ! $searchBar->isValid()) {
            if ($searchBar->hasBeenSubmitted()) {
                $filter = QueryString::parse($this->getFilter()->toQueryString());
            } else {
                $this->addControl($searchBar);
                $this->sendMultipartUpdate();
                return;
            }
        } else {
            $filter = $searchBar->getFilter();
        }

        $history->peekAhead();
        $history->limit($limitControl->getLimit());
        if ($page > 1) {
            if ($compact) {
                $history->offset(($page - 1) * $limitControl->getLimit());
            } else {
                $history->limit($page * $limitControl->getLimit());
            }
        }

        $this->filter($history, $filter);
        $history->getSelectBase()
            // Make sure we'll fetch service history entries only for services which still exist
            ->where(['history.service_id IS NULL', 'history_service.id IS NOT NULL'], Sql::ANY);

        yield $this->export($history);

        $results = $history->execute();

        $showMore = (new ShowMore(
            $results,
            $url->setParam('page', $page + 1)
                ->setAnchor('page-' . ($page + 1))
        ))
            ->setLabel(t('Load More'))
            ->setAttribute('data-no-icinga-ajax', true);

        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($searchBar);

        $historyList = (new HistoryList($results))
            ->setPageSize($limitControl->getLimit());
        if ($compact) {
            $historyList->setPageNumber($page);
        }

        // TODO: Dirty, really dirty, find a better solution (And I don't just mean `getContent()` !)
        $historyList->add($showMore->setTag('li')->addAttributes(['class' => 'list-item']));
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
}
