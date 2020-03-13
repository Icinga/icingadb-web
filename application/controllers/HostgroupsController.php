<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\Hostgroupsummary;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\HostgroupList;
use Icinga\Module\Icingadb\Widget\ShowMore;
use ipl\Web\Url;

class HostgroupsController extends Controller
{
    public function indexAction()
    {
        $this->setTitle($this->translate('Host Groups'));
        $compact = $this->view->compact;

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

        $hostgroups->peekAhead($compact);

        yield $this->export($hostgroups);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

        $results = $hostgroups->execute();

        $this->addContent(
            (new HostgroupList($results))->setBaseFilter($this->getFilter())
        );

        if ($compact) {
            $this->addContent(
                (new ShowMore($results, Url::fromRequest()->without(['view', 'limit'])))
                    ->setAttribute('data-base-target', '_next')
                    ->setAttribute('title', sprintf(
                        $this->translate('Show all %d hostgroups'),
                        $hostgroups->count()
                    ))
            );
        }

        $this->setAutorefreshInterval(30);
    }
}
