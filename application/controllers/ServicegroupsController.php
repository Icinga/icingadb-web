<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Model\Servicegroup;
use Icinga\Module\Icingadb\Model\ServicegroupSummary;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
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

        $this->handleSearchRequest($servicegroups);

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
        $searchBar = $this->createSearchBar($servicegroups, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam()
        ]);

        $this->filter($servicegroups, $searchBar->getFilter());

        $servicegroups->peekAhead($compact);

        yield $this->export($servicegroups);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($searchBar);

        $results = $servicegroups->execute();

        $this->addContent(
            (new ServicegroupList($results))->setBaseFilter($this->getFilter())
        );

        if ($compact) {
            $this->addContent(
                (new ShowMore($results, Url::fromRequest()->without(['showCompact', 'limit'])))
                    ->setAttribute('data-base-target', '_next')
                    ->setAttribute('title', sprintf(
                        t('Show all %d servicegroups'),
                        $servicegroups->count()
                    ))
            );
        }

        if ($searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }

        $this->setAutorefreshInterval(30);
    }

    public function completeAction()
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(Servicegroup::class);
        $suggestions->forRequest(ServerRequest::fromGlobals());
        $this->getDocument()->add($suggestions);
    }
}
