<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Model\Hostgroup;
use Icinga\Module\Icingadb\Model\Hostgroupsummary;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemTable\HostgroupTable;
use Icinga\Module\Icingadb\Widget\ShowMore;
use Icinga\Module\Icingadb\Web\Control\ViewModeSwitcher;
use ipl\Orm\Query;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;
use ipl\Web\Url;

class HostgroupsController extends Controller
{
    public function init()
    {
        parent::init();

        $this->assertRouteAccess();
    }

    public function indexAction()
    {
        $this->addTitleTab(t('Host Groups'));

        $db = $this->getDb();

        $hostgroups = Hostgroupsummary::on($db);

        yield from $this->renderHostGroups($hostgroups);
    }

    public function completeAction()
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(Hostgroup::class);
        $suggestions->forRequest(ServerRequest::fromGlobals());
        $this->getDocument()->add($suggestions);
    }

    public function searchEditorAction()
    {
        $editor = $this->createSearchEditor(Hostgroupsummary::on($this->getDb()), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM
        ]);

        $this->getDocument()->add($editor);
        $this->setTitle(t('Adjust Filter'));
    }

    public function gridAction()
    {
        $this->addTitleTab(t('Host Group Grid'));

        $db = $this->getDb();

        $hostgroups = Hostgroupsummary::on($db)->without([
            'services_critical_handled',
            'services_critical_unhandled',
            'services_ok',
            'services_pending',
            'services_total',
            'services_unknown_handled',
            'services_unknown_unhandled',
            'services_warning_handled',
            'services_warning_unhandled',
        ]);

        yield from $this->renderHostGroups($hostgroups);
    }

    protected function renderHostGroups(Query $hostgroups)
    {
        $compact = $this->view->compact;
        $this->handleSearchRequest($hostgroups);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($hostgroups);

        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);

        $defaultSort = null;
        if ($viewModeSwitcher->getViewMode() === 'minimal') {
            $defaultSort = ['hosts_severity DESC', 'display_name'];
        }

        $sortControl = $this->createSortControl(
            $hostgroups,
            [
                'display_name'                      => t('Name'),
                'hosts_severity desc, display_name' => t('Severity'),
                'hosts_total desc'                  => t('Total Hosts'),
            ],
            $defaultSort
        );

        $searchBar = $this->createSearchBar($hostgroups, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam(),
            $viewModeSwitcher->getViewModeParam()
        ]);

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

        $this->filter($hostgroups, $filter);

        $hostgroups->peekAhead($compact);

        yield $this->export($hostgroups);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($searchBar);

        $results = $hostgroups->execute();

        $this->addContent(
            (new HostgroupTable($results))->setBaseFilter($filter)
        );

        if ($compact) {
            $this->addContent(
                (new ShowMore($results, Url::fromRequest()->without(['showCompact', 'limit', 'view'])))
                    ->setBaseTarget('_next')
                    ->setAttribute('title', sprintf(
                        t('Show all %d hostgroups'),
                        $hostgroups->count()
                    ))
            );
        }

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }

        $this->setAutorefreshInterval(30);
    }
}
