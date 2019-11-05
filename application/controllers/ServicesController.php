<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ServiceList;

class ServicesController extends Controller
{
    public function indexAction()
    {
        $this->setTitle($this->translate('Services'));

        $db = $this->getDb();

        $services = Service::on($db)->with([
            'state',
            'host',
            'host.state'
        ]);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($services);
        $viewModeSwitcher = $this->createViewModeSwitcher();
        $filterControl = $this->createFilterControl($services);

        $this->filter($services);

        yield $this->export($services);

        $serviceList = (new ServiceList($services))
            ->setViewMode($viewModeSwitcher->getViewMode());

        $this->addControl($paginationControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

        $this->addContent($serviceList);
    }
}
