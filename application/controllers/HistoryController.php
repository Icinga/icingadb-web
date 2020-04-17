<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\HistoryList;
use Icinga\Module\Icingadb\Widget\ShowMore;
use ipl\Sql\Sql;
use ipl\Web\Url;

class HistoryController extends Controller
{
    public function indexAction()
    {
        $this->setTitle($this->translate('History'));
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

        $this->params->shift('view'); // TODO: Don't shift here, damn it (Nothing else does it)
        $url = Url::fromPath('icingadb/history')->setParams(clone $this->params);
        if (! $this->params->has('page') || ($page = (int) $this->params->shift('page')) < 1) {
            $page = 1;
        }

        $limitControl = $this->createLimitControl();
        $sortControl = $this->createSortControl(
            $history,
            [
                'history.event_time desc' => $this->translate('Event Time')
            ]
        );
        $filterControl = $this->createFilterControl($history);

        $history->peekAhead();
        $history->limit($limitControl->getLimit());
        if ($page > 1) {
            if ($compact) {
                $history->offset(($page - 1) * $limitControl->getLimit());
            } else {
                $history->limit($page * $limitControl->getLimit());
            }
        }

        $this->filter($history);
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
            ->setLabel($this->translate('Load More'))
            ->setAttribute('data-no-icinga-ajax', true);

        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

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
    }
}
