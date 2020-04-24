<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\ServicegroupSummary;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\ServicegroupList;
use Icinga\Module\Icingadb\Widget\ShowMore;
use ipl\Web\Url;

class ServicegroupsController extends Controller
{
    public function indexAction()
    {
        $this->setTitle(t('Service Groups'));
        $compact = $this->view->compact;

        $db = $this->getDb();

        $servicegroups = ServicegroupSummary::on($db);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($servicegroups);
        $sortControl = $this->createSortControl(
            $servicegroups,
            [
                'display_name'           => t('Name'),
                'services_severity desc' => t('Severity'),
                'services_total desc'    => t('Total Services')
            ]
        );
        $filterControl = $this->createFilterControl($servicegroups);

        $this->filter($servicegroups);

        $servicegroups->peekAhead($compact);

        yield $this->export($servicegroups);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

        $results = $servicegroups->execute();

        $this->addContent(
            (new ServicegroupList($results))->setBaseFilter($this->getFilter())
        );

        if ($compact) {
            $this->addContent(
                (new ShowMore($results, Url::fromRequest()->without(['view', 'limit'])))
                    ->setAttribute('data-base-target', '_next')
                    ->setAttribute('title', sprintf(
                        t('Show all %d servicegroups'),
                        $servicegroups->count()
                    ))
            );
        }

        $this->setAutorefreshInterval(30);
    }
}
