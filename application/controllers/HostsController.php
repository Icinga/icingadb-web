<?php

namespace Icinga\Module\Eagle\Controllers;

use Icinga\Module\Eagle\Model\Host;
use Icinga\Module\Eagle\Web\Controller;
use Icinga\Module\Eagle\Widget\HostList;

class HostsController extends Controller
{
    public function indexAction()
    {
        $this->setTitle($this->translate('Hosts'));

        $db = $this->getDb();

        $hosts = Host::on($db)->with('state');

        $viewModeSwitcher = $this->createViewModeSwitcher();
        $limitControl = $this->createLimitControl();

        $hosts->limit($limitControl->getLimit());

        $hostList = (new HostList($hosts))
            ->setRedis($this->getRedis())
            ->setViewMode($viewModeSwitcher->getViewMode());

        $this->addControl($this->createPaginationControl($hosts));
        $this->addControl($viewModeSwitcher);
        $this->addControl($limitControl);

        $this->addContent($hostList);
    }
}
