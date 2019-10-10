<?php

namespace Icinga\Module\Eagle\Controllers;

use Icinga\Module\Eagle\Model\Service;
use Icinga\Module\Eagle\Web\Controller;
use Icinga\Module\Eagle\Widget\ServiceList;

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

        $viewModeSwitcher = $this->createViewModeSwitcher();
        $limitControl = $this->createLimitControl();

        $services->limit($limitControl->getLimit());

        $serviceList = (new ServiceList($services))
            ->setRedis($this->getRedis())
            ->setViewMode($viewModeSwitcher->getViewMode());

        $this->addControl($this->createPaginationControl($services));
        $this->addControl($viewModeSwitcher);
        $this->addControl($limitControl);

        $this->addContent($serviceList);
    }
}
