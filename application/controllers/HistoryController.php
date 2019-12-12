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

        $url = Url::fromRequest();
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

        $history->limit($limitControl->getLimit() * $page);
        $this->filter($history);

        yield $this->export($history);

        $showMore = new ShowMore(
            $history->peekAhead()->execute(),
            (clone $url)->setParam('page', $page + 1)
                ->setAnchor('page-' . ($page + 1))
        );

        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

        $this->addContent((new HistoryList($history))->setPageSize($limitControl->getLimit()));
        $this->addContent($showMore);
    }
}
