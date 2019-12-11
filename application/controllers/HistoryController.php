<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\HistoryList;

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

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($history);
        $sortControl = $this->createSortControl(
            $history,
            [
                'history.event_time desc' => $this->translate('Event Time')
            ]
        );
        $filterControl = $this->createFilterControl($history);

        $this->filter($history);

        yield $this->export($history);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

        $this->addContent(new HistoryList($history));
    }
}
