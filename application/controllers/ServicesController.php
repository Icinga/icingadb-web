<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Common\CommandActions;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Data\PivotTable;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use Icinga\Module\Icingadb\Redis\VolatileStateResults;
use Icinga\Module\Icingadb\Util\FeatureStatus;
use Icinga\Module\Icingadb\Web\Control\ProblemToggle;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\MultiselectQuickActions;
use Icinga\Module\Icingadb\Widget\Detail\ObjectsDetail;
use Icinga\Module\Icingadb\Widget\ItemList\ObjectList;
use Icinga\Module\Icingadb\Widget\ItemTable\ServiceItemTable;
use Icinga\Module\Icingadb\Widget\ServiceStatusBar;
use Icinga\Module\Icingadb\Web\Control\ViewModeSwitcher;
use Icinga\Module\Icingadb\Widget\ShowMore;
use Icinga\Util\Environment;
use ipl\Html\HtmlString;
use ipl\Orm\Query;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;
use ipl\Web\Url;

class ServicesController extends Controller
{
    use CommandActions;

    public function indexAction()
    {
        $this->addTitleTab(t('Services'));
        $compact = $this->view->compact;

        $db = $this->getDb();

        $services = Service::on($db)->with([
            'state',
            'state.last_comment',
            'host',
            'host.state',
            'icon_image'
        ]);
        $services->getWith()['service.state']->setJoinType('INNER');
        $services->setResultSetClass(VolatileStateResults::class);

        $this->handleSearchRequest($services);

        $summary = null;
        if (! $compact) {
            $summary = ServicestateSummary::on($db);
        }

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($services);
        $sortControl = $this->createSortControl(
            $services,
            [
                'service.display_name'                                             => t('Name'),
                'service.state.severity desc,service.state.last_state_change desc' => t('Severity'),
                'service.state.soft_state'                                         => t('Current State'),
                'service.state.last_state_change desc'                             => t('Last State Change'),
                'host.display_name'                                                => t('Host')
            ],
            ['service.state.severity DESC', 'service.state.last_state_change DESC']
        );
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);
        $columns = $this->createColumnControl($services, $viewModeSwitcher);

        $searchBar = $this->createSearchBar($services, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam(),
            $viewModeSwitcher->getViewModeParam(),
            'columns'
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

        $services->peekAhead($compact);

        $this->filter($services, $filter);
        if (! $compact) {
            $this->filter($summary, $filter);
            yield $this->export($services, $summary);
        } else {
            yield $this->export($services);
        }

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($searchBar);
        $continueWith = $this->createContinueWith(Links::servicesDetails(), $searchBar);

        $results = $services->execute();

        if ($viewModeSwitcher->getViewMode() === 'tabular') {
            $serviceList = (new ServiceItemTable($results, ServiceItemTable::applyColumnMetaData($services, $columns)))
                ->setSort($sortControl->getSort());
        } else {
            $serviceList = (new ObjectList($results))
                ->setViewMode($viewModeSwitcher->getViewMode());
        }

        $this->addContent($serviceList);

        if ($compact) {
            $this->addContent(
                (new ShowMore($results, Url::fromRequest()->without(['showCompact', 'limit', 'view'])))
                    ->setBaseTarget('_next')
                    ->setAttribute('title', sprintf(
                        t('Show all %d services'),
                        $services->count()
                    ))
            );
        } else {
            /** @var ServicestateSummary $servicesSummary */
            $servicesSummary = $summary->first();
            $this->addFooter((new ServiceStatusBar($servicesSummary))->setBaseFilter($filter));
        }

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate($continueWith);
        }

        $this->setAutorefreshInterval(10);
    }

    public function detailsAction()
    {
        $this->addTitleTab(t('Services'));

        $db = $this->getDb();

        $services = Service::on($db)->with([
            'state',
            'icon_image',
            'host',
            'host.state'
        ]);
        $services->setResultSetClass(VolatileStateResults::class);
        $summary = ServicestateSummary::on($db)->with(['state']);

        $this->filter($services);
        $this->filter($summary);

        $services->limit(3);
        $services->peekAhead();

        yield $this->export($services, $summary);

        $results = $services->execute();
        $summary = $summary->first();

        $downtimes = Service::on($db)->with(['downtime']);
        $downtimes->getWith()['service.downtime']->setJoinType('INNER');
        $this->filter($downtimes);
        $summary->downtimes_total = $downtimes->count();

        $comments = Service::on($db)->with(['comment']);
        $comments->getWith()['service.comment']->setJoinType('INNER');
        // TODO: This should be automatically done by the model/resolver and added as ON condition
        $comments->filter(Filter::equal('comment.object_type', 'service'));
        $this->filter($comments);
        $summary->comments_total = $comments->count();

        $this->addControl(
            (new ObjectList($results))
                ->setViewMode('minimal')
                ->setDetailActionsDisabled()
        );
        $this->addControl(new ShowMore(
            $results,
            Links::services()->setFilter($this->getFilter()),
            sprintf(t('Show all %d services'), $services->count())
        ));
        $this->addControl(
            (new MultiselectQuickActions('service', $summary))
                ->setBaseFilter($this->getFilter())
        );

        $this->addContent(
            (new ObjectsDetail('service', $summary, $services))
                ->setBaseFilter($this->getFilter())
        );
    }

    public function completeAction()
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(Service::class);
        $suggestions->forRequest(ServerRequest::fromGlobals());
        $this->getDocument()->add($suggestions);
    }

    public function searchEditorAction()
    {
        $editor = $this->createSearchEditor(Service::on($this->getDb()), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM,
            'columns'
        ]);

        $this->getDocument()->add($editor);
        $this->setTitle(t('Adjust Filter'));
    }

    public function gridAction()
    {
        Environment::raiseExecutionTime();

        $db = $this->getDb();
        $this->addTitleTab(t('Service Grid'));

        $query = Service::on($db)->with([
            'state',
            'host',
            'host.state'
        ]);
        $query->setResultSetClass(VolatileStateResults::class);

        $this->handleSearchRequest($query);

        $this->params->shift('page'); // Handled by PivotTable internally
        $this->params->shift('limit'); // Handled by PivotTable internally
        $flipped = $this->params->shift('flipped', false);

        $problemToggle = $this->createProblemToggle();
        $sortControl = $this->createSortControl($query, [
            'service.display_name' => t('Service Name'),
            'host.display_name' => t('Host Name'),
        ])->setDefault('service.display_name');
        $searchBar = $this->createSearchBar($query, [
            LimitControl::DEFAULT_LIMIT_PARAM,
            $sortControl->getSortParam(),
            'flipped',
            'page',
            'problems'
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

        $this->filter($query, $filter);

        $this->addControl($problemToggle);
        $this->addControl($sortControl);
        $this->addControl($searchBar);
        $continueWith = $this->createContinueWith(Links::servicesDetails(), $searchBar);

        $pivotFilter = $problemToggle->isChecked() ?
            Filter::equal('service.state.is_problem', 'y') : null;

        $columns = [
            'id',
            'host.id',
            'host_name' => 'host.name',
            'host_display_name' => 'host.display_name',
            'name' => 'service.name',
            'display_name' => 'service.display_name',
            'service.state.is_handled',
            'service.state.output',
            'service.state.soft_state'
        ];

        if ($flipped) {
            $pivot = (new PivotTable($query, 'host_name', 'name', $columns))
                ->setXAxisFilter($pivotFilter)
                ->setYAxisFilter($pivotFilter ? clone $pivotFilter : null)
                ->setXAxisHeader('host_display_name')
                ->setYAxisHeader('display_name');
        } else {
            $pivot = (new PivotTable($query, 'name', 'host_name', $columns))
                ->setXAxisFilter($pivotFilter)
                ->setYAxisFilter($pivotFilter ? clone $pivotFilter : null)
                ->setXAxisHeader('display_name')
                ->setYAxisHeader('host_display_name');
        }

        $this->view->horizontalPaginator = $pivot->paginateXAxis();
        $this->view->verticalPaginator = $pivot->paginateYAxis();
        list($pivotData, $pivotHeader) = $pivot->toArray();
        $this->view->pivotData = $pivotData;
        $this->view->pivotHeader = $pivotHeader;

        /** Preserve filter and params in view links (the `BaseFilter` implementation for view scripts -.-) */
        $this->view->baseUrl = Url::fromRequest()
            ->onlyWith([
                LimitControl::DEFAULT_LIMIT_PARAM,
                $sortControl->getSortParam(),
                'flipped',
                'page',
                'problems'
            ]);
        $preservedParams = $this->view->baseUrl->getParams();
        $this->view->baseUrl->setFilter($filter);

        $searchBar->setEditorUrl(Url::fromPath(
            "icingadb/services/grid-search-editor"
        )->setParams($preservedParams));

        $this->view->controls = $this->controls;

        if ($flipped) {
            $this->getHelper('viewRenderer')->setScriptAction('grid-flipped');
        }

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            // TODO: Everything up to addContent() (inclusive) can be removed once the grid is a widget
            $this->view->controls = ''; // Relevant controls are transmitted separately
            $viewRenderer = $this->getHelper('viewRenderer');
            $viewRenderer->postDispatch();
            $viewRenderer->setNoRender(false);

            $content = trim($this->getResponse());
            $this->getResponse()->clearBody($viewRenderer->getResponseSegment());

            $this->addContent(HtmlString::create(substr($content, strpos($content, '>') + 1, -6)));

            $this->sendMultipartUpdate($continueWith);
        }

        $this->setAutorefreshInterval(30);
    }

    public function gridSearchEditorAction()
    {
        $editor = $this->createSearchEditor(
            Service::on($this->getDb()),
            Url::fromPath('icingadb/services/grid'),
            [
                LimitControl::DEFAULT_LIMIT_PARAM,
                SortControl::DEFAULT_SORT_PARAM,
                'flipped',
                'page',
                'problems'
            ]
        );

        $this->getDocument()->add($editor);
        $this->setTitle(t('Adjust Filter'));
    }

    protected function fetchCommandTargets(): Query
    {
        $db = $this->getDb();

        $services = Service::on($db)->with([
            'state',
            'host',
            'host.state'
        ]);
        $services->setResultSetClass(VolatileStateResults::class);

        switch ($this->getRequest()->getActionName()) {
            case 'acknowledge':
                $services->filter(Filter::equal('state.is_problem', 'y'))
                    ->filter(Filter::equal('state.is_acknowledged', 'n'));

                break;
        }

        $this->filter($services);

        return $services;
    }

    protected function getCommandTargetsUrl(): Url
    {
        return Links::servicesDetails()->setFilter($this->getFilter());
    }

    protected function getFeatureStatus()
    {
        $summary = ServicestateSummary::on($this->getDb());
        $this->filter($summary);

        return new FeatureStatus('service', $summary->first());
    }

    protected function prepareSearchFilter(Query $query, string $search, Filter\Any $filter, array $additionalColumns)
    {
        if ($this->params->shift('_hostFilterOnly', false)) {
            foreach (['host.name_ci', 'host.display_name', 'host.address', 'host.address6'] as $column) {
                $filter->add(Filter::like($column, "*$search*"));
            }
        } else {
            parent::prepareSearchFilter($query, $search, $filter, $additionalColumns);
        }
    }

    public function createProblemToggle(): ProblemToggle
    {
        $filter = $this->params->shift('problems');

        $problemToggle = new ProblemToggle($filter);
        $problemToggle->setIdProtector([$this->getRequest(), 'protectId']);

        $problemToggle->on(ProblemToggle::ON_SUCCESS, function (ProblemToggle $form) {
            if (! $form->getElement('problems')->isChecked()) {
                $this->redirectNow(Url::fromRequest()->remove('problems'));
            } else {
                $this->redirectNow(Url::fromRequest()->setParams($this->params->add('problems')));
            }
        })->handleRequest(ServerRequest::fromGlobals());

        return $problemToggle;
    }
}
