<?php

namespace Icinga\Module\Eagle\Controllers;

use Icinga\Module\Eagle\Model\Downtime;
use Icinga\Module\Eagle\Web\Controller;
use Icinga\Module\Eagle\Widget\DowntimeList;

class DowntimesController extends Controller
{
    public function indexAction()
    {
        $this->setTitle($this->translate('Downtimes'));

        $db = $this->getDb();

        $downtimes = Downtime::on($db)->with([
            'host',
            'host.state',
            'service',
            'service.host',
            'service.host.state',
            'service.state'
        ]);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($downtimes);
        $filterControl = $this->createFilterControl($downtimes);

        $this->filter($downtimes);

        yield $this->export($downtimes);

        $this->addControl($paginationControl);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

        $this->addContent(new DowntimeList($downtimes));
    }
}
