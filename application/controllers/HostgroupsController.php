<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\Hostgroupsummary;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\HostgroupList;

class HostgroupsController extends Controller
{
    public function indexAction() {

        $this->setTitle($this->translate('Host Groups'));

        $db = $this->getDb();

        $hostgroups = Hostgroupsummary::on($db);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($hostgroups);
        $sortControl = $this->createSortControl(
            $hostgroups,
            [
                'display_name'        => $this->translate('Name'),
                'hosts_severity desc' => $this->translate('Severity'),
                'hosts_total desc'    => $this->translate('Total Hosts'),
                'services_total desc' => $this->translate('Total Services')
            ]
        );
        $filterControl = $this->createFilterControl($hostgroups);

        $this->filter($hostgroups);

        yield $this->export($hostgroups);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

        $this->addContent(
            (new HostgroupList($hostgroups))->setBaseFilter($this->getFilter())
        );

        $this->setAutorefreshInterval(30);
    }
}
