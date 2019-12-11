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
        $sortControl = $this->createSortControl(
            $hosts,
            [
                'host.display_name'                 => $this->translate('Name'),
                'host.state.severity desc'          => $this->translate('Severity'),
                'host.state.soft_state'             => $this->translate('Current State'),
                'host.state.last_state_change desc' => $this->translate('Last State Change')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher();
        $filterControl = $this->createFilterControl($hosts);

        $hostList = (new HostList($hosts))
            ->setViewMode($viewModeSwitcher->getViewMode());

        $this->filter($hosts);
        $this->filter($summary);

        yield $this->export($hosts, $summary);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($filterControl);

        $this->addContent($hostList);

        $this->setAutorefreshInterval(10);
    }
}
