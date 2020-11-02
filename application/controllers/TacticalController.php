<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Data\Filter\Filter;
use Icinga\Module\Icingadb\Model\HoststateSummary;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\HostSummaryDonut;
use Icinga\Module\Icingadb\Widget\ServiceSummaryDonut;
use ipl\Web\Filter\QueryString;

class TacticalController extends Controller
{
    public function indexAction()
    {
        $this->setTitle(t('Tactical Overview'));

        $db = $this->getDb();

        $hoststateSummary = HoststateSummary::on($db)->with('state');
        // With relation `host` because otherwise the filter editor only presents service cols
        $servicestateSummary = ServicestateSummary::on($db)->with(['state', 'host']);

        $this->handleSearchRequest($servicestateSummary);

        $searchBar = $this->createSearchBar($servicestateSummary);

        $filter = $searchBar->getFilter();

        $this->filter($hoststateSummary, $filter);
        $this->filter($servicestateSummary, $filter);

        yield $this->export($hoststateSummary, $servicestateSummary);

        $this->addControl($searchBar);

        $this->addContent(
            (new HostSummaryDonut($hoststateSummary->first()))
                ->setBaseFilter(Filter::fromQueryString(QueryString::render($filter)))
        );

        $this->addContent(
            (new ServiceSummaryDonut($servicestateSummary->first()))
                ->setBaseFilter(Filter::fromQueryString(QueryString::render($filter)))
        );

        if ($searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }

        $this->setAutorefreshInterval(10);
    }

    public function completeAction()
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(ServicestateSummary::class);
        $suggestions->forRequest(ServerRequest::fromGlobals());
        $this->getDocument()->add($suggestions);
    }
}
