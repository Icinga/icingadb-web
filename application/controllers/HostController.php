<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use ArrayIterator;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Command\Object\GetObjectCommand;
use Icinga\Module\Icingadb\Command\Transport\CommandTransport;
use Icinga\Module\Icingadb\Common\Backend;
use Icinga\Module\Icingadb\Common\CommandActions;
use Icinga\Module\Icingadb\Common\HostLinks;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Hook\TabHook\HookActions;
use Icinga\Module\Icingadb\Model\DependencyEdge;
use Icinga\Module\Icingadb\Model\DependencyNode;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use Icinga\Module\Icingadb\Redis\VolatileStateResults;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Control\ViewModeSwitcher;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\HostDetail;
use Icinga\Module\Icingadb\Widget\Detail\HostInspectionDetail;
use Icinga\Module\Icingadb\Widget\Detail\HostMetaInfo;
use Icinga\Module\Icingadb\Widget\Detail\ObjectHeader;
use Icinga\Module\Icingadb\Widget\Detail\QuickActions;
use Icinga\Module\Icingadb\Widget\ItemList\LoadMoreObjectList;
use Icinga\Module\Icingadb\Widget\ItemList\ObjectList;
use ipl\Orm\Query;
use ipl\Sql\Expression;
use ipl\Sql\Filter\Exists;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;
use ipl\Web\Url;
use ipl\Web\Widget\Tabs;
use Generator;

class HostController extends Controller
{
    use CommandActions;
    use HookActions;

    /** @var Host The host object */
    protected $host;

    public function init(): void
    {
        $name = $this->params->shiftRequired('name');

        $query = Host::on($this->getDb())->with(['state', 'icon_image', 'timeperiod']);
        $query
            ->setResultSetClass(VolatileStateResults::class)
            ->filter(Filter::equal('host.name', $name));

        $this->applyRestrictions($query);

        /** @var Host $host */
        $host = $query->first();
        if ($host === null) {
            throw new NotFoundError($this->translate('Host not found'));
        }

        $this->host = $host;
        $this->loadTabsForObject($host);

        $this->addControl(new ObjectHeader($host));

        $this->setTitleTab($this->getRequest()->getActionName());
        $this->setTitle($host->display_name);
    }

    public function indexAction(): void
    {
        $serviceSummary = ServicestateSummary::on($this->getDb());
        $serviceSummary->filter(Filter::equal('service.host_id', $this->host->id));

        $this->applyRestrictions($serviceSummary);

        if ($this->host->state->is_overdue) {
            $this->controls->addAttributes(['class' => 'overdue']);
        }

        $this->addControl(new HostMetaInfo($this->host));
        $this->addControl(new QuickActions($this->host));

        $this->addContent(new HostDetail($this->host, $serviceSummary->first()));

        $this->setAutorefreshInterval(10);
    }

    public function sourceAction(): void
    {
        $this->assertPermission('icingadb/object/show-source');

        $apiResult = (new CommandTransport())->send(
            (new GetObjectCommand())
                ->setObjects(new ArrayIterator([$this->host]))
        );

        if ($this->host->state->is_overdue) {
            $this->controls->addAttributes(['class' => 'overdue']);
        }

        $this->addContent(new HostInspectionDetail(
            $this->host,
            reset($apiResult)
        ));
    }

    public function historyAction(): Generator
    {
        $compact = $this->view->compact; // TODO: Find a less-legacy way..

        if ($this->host->state->is_overdue) {
            $this->controls->addAttributes(['class' => 'overdue']);
        }

        $db = $this->getDb();

        $history = History::on($db)->with([
            'host',
            'host.state',
            'comment',
            'downtime',
            'flapping',
            'notification',
            'acknowledgement',
            'state'
        ]);

        $history->filter(Filter::all(
            Filter::equal('history.host_id', $this->host->id),
            Filter::unlike('history.service_id', '*')
        ));

        $before = $this->params->shift('before', time());
        $url = Url::fromRequest()->setParams(clone $this->params);
        $url->setParam('name', $this->host->name);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($history);
        $sortControl = $this->createSortControl(
            $history,
            [
                'history.event_time desc, history.event_type desc' => $this->translate('Event Time')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl, true);

        $history->peekAhead();

        $page = $paginationControl->getCurrentPageNumber();

        if ($page > 1 && ! $compact) {
            $history->limit($page * $limitControl->getLimit());
        }

        $history->filter(Filter::lessThanOrEqual('event_time', $before));

        yield $this->export($history);

        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);

        $historyList = (new LoadMoreObjectList($history->execute()))
            ->setViewMode($viewModeSwitcher->getViewMode())
            ->setPageSize($limitControl->getLimit())
            ->setLoadMoreUrl($url->setParam('before', $before));

        if ($compact) {
            $historyList->setPageNumber($page);
        }

        if ($compact && $page > 1) {
            $this->document->addFrom($historyList);
        } else {
            $this->addContent($historyList);
        }
    }

    public function servicesAction(): Generator
    {
        if ($this->host->state->is_overdue) {
            $this->controls->addAttributes(['class' => 'overdue']);
        }

        $db = $this->getDb();

        $services = Service::on($db)->with([
            'state',
            'state.last_comment',
            'icon_image',
            'host',
            'host.state'
        ]);
        $services
            ->setResultSetClass(VolatileStateResults::class)
            ->filter(Filter::equal('host.id', $this->host->id));

        $this->applyRestrictions($services);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($services);
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);
        $sortControl = $this->createSortControl(
            $services,
            [
                'service.display_name' => $this->translate('Name'),
                'service.state.severity desc,service.state.last_state_change desc' => $this->translate('Severity'),
                'service.state.soft_state' => $this->translate('Current State'),
                'service.state.last_state_change desc' => $this->translate('Last State Change')
            ]
        );

        yield $this->export($services);

        $serviceList = (new ObjectList($services))
            ->setViewMode($viewModeSwitcher->getViewMode());

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);

        $this->addContent($serviceList);

        $this->setAutorefreshInterval(10);
    }

    public function parentsAction(): Generator
    {
        $nodesQuery = $this->fetchDependencyNodes(true);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($nodesQuery);
        $sortControl = $this->createSortControl(
            $nodesQuery,
            [
                'name'                                  => $this->translate('Name'),
                'severity desc, last_state_change desc' => $this->translate('Severity'),
                'state'                                 => $this->translate('Current State'),
                'last_state_change desc'                => $this->translate('Last State Change')
            ]
        );

        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);

        $searchBar = $this->createSearchBar(
            $nodesQuery,
            [
                $limitControl->getLimitParam(),
                $sortControl->getSortParam(),
                $viewModeSwitcher->getViewModeParam(),
                'name'
            ]
        );

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

        $nodesQuery->filter($filter);

        yield $this->export($nodesQuery);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($searchBar);

        $this->addContent(
            (new ObjectList($nodesQuery))
                ->setViewMode($viewModeSwitcher->getViewMode())
        );

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }

        $this->setAutorefreshInterval(10);
    }

    public function childrenAction(): Generator
    {
        $nodesQuery = $this->fetchDependencyNodes();

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($nodesQuery);
        $sortControl = $this->createSortControl(
            $nodesQuery,
            [
                'name'                                  => $this->translate('Name'),
                'severity desc, last_state_change desc' => $this->translate('Severity'),
                'state'                                 => $this->translate('Current State'),
                'last_state_change desc'                => $this->translate('Last State Change')
            ]
        );

        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);

        $preserveParams = [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam(),
            $viewModeSwitcher->getViewModeParam(),
            'name'
        ];

        $requestParams = Url::fromRequest()->onlyWith($preserveParams)->getParams();
        $searchBar = $this->createSearchBar($nodesQuery, $preserveParams)
            ->setEditorUrl(
                Url::fromPath('icingadb/host/children-search-editor')
                    ->setParams($requestParams)
            )->setSuggestionUrl(
                Url::fromPath('icingadb/host/children-complete')
                    ->setParams(clone $requestParams)
            );

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

        $nodesQuery->filter($filter);

        yield $this->export($nodesQuery);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($searchBar);

        $this->addContent(
            (new ObjectList($nodesQuery))
                ->setViewMode($viewModeSwitcher->getViewMode())
        );

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }

        $this->setAutorefreshInterval(10);
    }

    public function completeAction(): void
    {
        $suggestions = (new ObjectSuggestions())
            ->setModel(DependencyNode::class)
            ->onlyWithCustomVarSources(['host', 'service', 'hostgroup', 'servicegroup'])
            ->setBaseFilter(Filter::equal("child.host.id", $this->host->id))
            ->forRequest($this->getServerRequest());

        $this->getDocument()->add($suggestions);
    }

    public function childrenCompleteAction(): void
    {
        $suggestions = (new ObjectSuggestions())
            ->setModel(DependencyNode::class)
            ->onlyWithCustomVarSources(['host', 'service', 'hostgroup', 'servicegroup'])
            ->setBaseFilter(Filter::equal("parent.host.id", $this->host->id))
            ->forRequest($this->getServerRequest());

        $this->getDocument()->add($suggestions);
    }

    public function searchEditorAction(): void
    {
        $editor = $this->createSearchEditor(
            DependencyNode::on($this->getDb()),
            Url::fromPath('icingadb/host/parents', ['name' => $this->host->name]),
            [
                LimitControl::DEFAULT_LIMIT_PARAM,
                SortControl::DEFAULT_SORT_PARAM,
                ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM,
                'name'
            ]
        );

        $this->getDocument()->add($editor);
        $this->setTitle($this->translate('Adjust Filter'));
    }

    public function childrenSearchEditorAction(): void
    {
        $preserveParams = [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM,
            'name'
        ];

        $editor = $this->createSearchEditor(
            DependencyNode::on($this->getDb()),
            Url::fromPath('icingadb/host/children', ['name' => $this->host->name]),
            $preserveParams
        );

        $editor->setSuggestionUrl(
            Url::fromPath('icingadb/host/children-complete')
                ->setParams(Url::fromRequest()->onlyWith($preserveParams)->getParams())
        );

        $this->getDocument()->add($editor);
        $this->setTitle($this->translate('Adjust Filter'));
    }

    /**
     * Fetch the dependency nodes of the current host
     *
     * @param bool $parents Whether to fetch the parents or the children
     *
     * @return Query
     */
    protected function fetchDependencyNodes(bool $parents = false): Query
    {
        $query = DependencyNode::on($this->getDb())
            ->with([
                'host',
                'host.state',
                'host.state.last_comment',
                'service',
                'service.state',
                'service.state.last_comment',
                'service.host',
                'service.host.state',
                'redundancy_group',
                'redundancy_group.state'
            ])
            ->setResultSetClass(VolatileStateResults::class);

        $this->joinFix($query, $this->host->id, $parents);

        $this->applyRestrictions($query);

        return $query;
    }

    protected function createTabs(): Tabs
    {
        if (! Backend::supportsDependencies()) {
            $hasDependencyNode = false;
        } else {
            $hasDependencyNode = DependencyNode::on($this->getDb())
                ->columns([new Expression('1')])
                ->filter(Filter::all(
                    Filter::equal('host_id', $this->host->id),
                    Filter::unlike('service_id', '*')
                ))
                ->disableDefaultSort()
                ->first() !== null;
        }

        $tabs = $this->getTabs()
            ->add('index', [
                'label'  => $this->translate('Host'),
                'url'    => Links::host($this->host)
            ])
            ->add('services', [
                'label'  => $this->translate('Services'),
                'url'    => HostLinks::services($this->host)
            ])
            ->add('history', [
                'label' => $this->translate('History'),
                'url' => HostLinks::history($this->host)
            ]);

        if ($hasDependencyNode) {
            $tabs->add('parents', [
                'label' => $this->translate('Parents'),
                'url'   => Url::fromPath('icingadb/host/parents', ['name' => $this->host->name])
            ])->add('children', [
                'label' => $this->translate('Children'),
                'url'   => Url::fromPath('icingadb/host/children', ['name' => $this->host->name])
            ]);
        }

        if ($this->hasPermission('icingadb/object/show-source')) {
            $tabs->add('source', [
                'label' => $this->translate('Source'),
                'url' => Links::hostSource($this->host)
            ]);
        }

        foreach ($this->loadAdditionalTabs() as $name => $tab) {
            $tabs->add($name, $tab + ['urlParams' => ['name' => $this->host->name]]);
        }

        return $tabs;
    }

    protected function setTitleTab(string $name): void
    {
        $tab = $this->createTabs()->get($name);

        if ($tab !== null) {
            $this->getTabs()->activate($name);
        }
    }

    protected function fetchCommandTargets(): array
    {
        return [$this->host];
    }

    protected function getCommandTargetsUrl(): Url
    {
        return Links::host($this->host);
    }

    protected function getDefaultTabControls(): array
    {
        return [new ObjectHeader($this->host)];
    }

    /**
     * Filter the query to only include (direct) parents or children of the given object.
     *
     * @todo This is a workaround, remove it once https://github.com/Icinga/ipl-orm/issues/76 is fixed
     *
     * @param Query $query
     * @param string $objectId
     * @param bool $fetchParents Fetch parents if true, children otherwise
     */
    protected function joinFix(Query $query, string $objectId, bool $fetchParents = false): void
    {
        $filterTable = $fetchParents ? 'child' : 'parent';
        $utilizeType = $fetchParents ? 'parent' : 'child';

        $edge = DependencyEdge::on($this->getDb())
            ->utilize($utilizeType)
            ->columns([new Expression('1')])
            ->filter(Filter::equal("$filterTable.host.id", $objectId))
            ->filter(Filter::unlike("$filterTable.service.id", '*'));

        $edge->getFilter()->metaData()->set('forceOptimization', false);

        $resolver = $edge->getResolver();

        $edgeAlias = $resolver->getAlias(
            $resolver->resolveRelation($resolver->qualifyPath($utilizeType, $edge->getModel()->getTableName()))
                ->getTarget()
        );

        $query->filter(new Exists(
            $edge->assembleSelect()
                ->where(
                    "$edgeAlias.id = "
                    . $query->getResolver()->qualifyColumn('id', $query->getModel()->getTableName())
                )
        ));
    }
}
