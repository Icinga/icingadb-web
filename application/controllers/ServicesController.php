<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
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
        $summary = ServicestateSummary::on($db)->with('state');

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($services);
        $sortControl = $this->createSortControl(
            $services,
            [
                'service.display_name, host.display_name' => $this->translate('Name'),
                'service.state.severity desc'             => $this->translate('Severity'),
                'service.state.soft_state'                => $this->translate('Current State'),
                'service.state.last_state_change desc'    => $this->translate('Last State Change'),
                'host.display_name, service.display_name' => $this->translate('Host')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher();
        $filterControl = $this->createFilterControl($services);

        $this->filter($services);
        $this->filter($summary);

        yield $this->export($services, $summary);

        $serviceList = (new ServiceList($services))
            ->setViewMode($viewModeSwitcher->getViewMode());

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($filterControl);

        $this->addContent($serviceList);

        $this->setAutorefreshInterval(10);
    }
}
