<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\HistoryList;
use Icinga\Module\Icingadb\Widget\ShowMore;
use ipl\Web\Url;

class HistoryController extends Controller
{
    public function indexAction()
    {
        $this->setTitle($this->translate('History'));
        $compact = $this->params->shift('view') === 'compact'; // TODO: Don't shift here..

        $db = $this->getDb();

        $history = History::on($db)->with([
            'host',
            'host.state',
            'service',
            'service.state',
            'service.host',
            'service.host.state',
            'comment',
            'downtime',
            'notification',
            'state'
        ]);

        $url = Url::fromPath('icingadb/history')->setParams($this->params);
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

        $history->limit($limitControl->getLimit());
        if ($page > 1) {
            if ($compact) {
                $history->offset(($page - 1) * $limitControl->getLimit());
            } else {
                $history->limit($page * $limitControl->getLimit());
            }
        }

        $this->filter($history);

        yield $this->export($history);

        $showMore = (new ShowMore(
            $history->peekAhead()->execute(),
            (clone $url)->setParam('page', $page + 1)
                ->setAnchor('page-' . ($page + 1))
        ))
            ->setLabel('Load More')
            ->setAttribute('data-no-icinga-ajax', true);

        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

        $historyList = (new HistoryList($history))
            ->setPageSize($limitControl->getLimit());
        if ($compact) {
            $historyList->setPageNumber($page);
        }

        // TODO: Dirty, really dirty, find a better solution (And I don't just mean `getContent()` !)
        $historyList->add($showMore->setTag('li')->addAttributes(['class' => 'list-item']));
        if ($compact && $page > 1) {
            $this->document->add($historyList->getContent());
        } else {
            $this->addContent($historyList);
        }
    }
}
