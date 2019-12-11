<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\HoststateSummary;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\HostList;

class HostsController extends Controller
{
    public function indexAction()
    {
        $this->setTitle($this->translate('Hosts'));

        $db = $this->getDb();

        $hosts = Host::on($db)->with('state');
        $summary = HoststateSummary::on($db)->with('state');

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($hosts);
        $viewModeSwitcher = $this->createViewModeSwitcher();
        $filterControl = $this->createFilterControl($hosts);

        $hostList = (new HostList($hosts))
            ->setViewMode($viewModeSwitcher->getViewMode());

        $this->filter($hosts);
        $this->filter($summary);

        yield $this->export($hosts, $summary);

        $this->addControl($paginationControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($filterControl);

        $this->addContent($hostList);

        $this->setAutorefreshInterval(10);
    }
}
