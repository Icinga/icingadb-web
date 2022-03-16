<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Model\HoststateSummary;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\HostSummaryDonut;
use Icinga\Module\Icingadb\Widget\ServiceSummaryDonut;
use Icinga\Module\Icingadb\Web\Control\ViewModeSwitcher;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;

class TacticalController extends Controller
{
    public function indexAction()
    {
        $this->addTitleTab(t('Tactical Overview'));

        $db = $this->getDb();

        $hoststateSummary = HoststateSummary::on($db)->with('state');
        // With relation `host` because otherwise the filter editor only presents service cols
        $servicestateSummary = ServicestateSummary::on($db)->with(['state', 'host']);

        $this->handleSearchRequest($servicestateSummary);

        $searchBar = $this->createSearchBar($servicestateSummary);
        if ($searchBar->hasBeenSent() && ! $searchBar->isValid()) {
            if ($searchBar->hasBeenSubmitted()) {
                $filter = $this->getFilter();
            } else {
                $this->addControl($searchBar);
                $this->sendMultipartUpdate();
                return;
            }
        } else {
            $filter = $searchBar->getFilter();
        }

        $this->filter($hoststateSummary, $filter);
        $this->filter($servicestateSummary, $filter);

        yield $this->export($hoststateSummary, $servicestateSummary);

        $this->addControl($searchBar);

        $this->addContent(
            (new HostSummaryDonut($hoststateSummary->first()))
                ->setBaseFilter($filter)
        );

        $this->addContent(
            (new ServiceSummaryDonut($servicestateSummary->first()))
                ->setBaseFilter($filter)
        );

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
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

    public function searchEditorAction()
    {
        $editor = $this->createSearchEditor(ServicestateSummary::on($this->getDb()), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM
        ]);

        $this->getDocument()->add($editor);
        $this->setTitle(t('Adjust Filter'));
    }
}
