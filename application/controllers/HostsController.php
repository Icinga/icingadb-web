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

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($hosts);
        $viewModeSwitcher = $this->createViewModeSwitcher();

        $hostList = (new HostList($hosts))
            ->setViewMode($viewModeSwitcher->getViewMode());

        yield $this->export($hosts);

        $this->addControl($paginationControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($limitControl);

        $this->addContent($hostList);
    }
}
