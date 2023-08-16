<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Model\TacticallineSummary;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemTable\TacticallineTableRow;
use ipl\Orm\Query;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;

class TacticallineController extends Controller
{
    public function indexAction()
    {

##########ob_start();

        $this->addTitleTab(t('Tactical Line'));

        $db = $this->getDb();

        $tacticallineSummary = TacticallineSummary::on($db);

#        $searchBar = $this->createSearchBar($tacticallineSummary);
#        if ($searchBar->hasBeenSent() && ! $searchBar->isValid()) {
#            if ($searchBar->hasBeenSubmitted()) {
               $filter = $this->getFilter();
#            } else {
#                $this->addControl($searchBar);
#                $this->sendMultipartUpdate();
#                return;
#            }
#        } else {
#            $filter = $searchBar->getFilter();
#        }

        $this->filter($tacticallineSummary, $filter);

        yield $this->export($tacticallineSummary);

#        $this->addControl($searchBar);

        $this->addContent(
               (new TacticallineTableRow($tacticallineSummary->first()))
                   ->setBaseFilter($filter)
        );


#        $this->addContent(
#            (new ServiceSummaryDonut($servicestateSummary->first()))
#                ->setBaseFilter($filter)
#        );

#        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
#            $this->sendMultipartUpdate();
#        }

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
