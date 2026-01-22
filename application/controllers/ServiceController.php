<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use ArrayIterator;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Command\Object\GetObjectCommand;
use Icinga\Module\Icingadb\Command\Transport\CommandTransport;
use Icinga\Module\Icingadb\Common\Backend;
use Icinga\Module\Icingadb\Common\CommandActions;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\ServiceLinks;
use Icinga\Module\Icingadb\Hook\TabHook\HookActions;
use Icinga\Module\Icingadb\Model\DependencyNode;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Redis\VolatileStateResults;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Control\ViewModeSwitcher;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\ObjectHeader;
use Icinga\Module\Icingadb\Widget\Detail\QuickActions;
use Icinga\Module\Icingadb\Widget\Detail\ServiceDetail;
use Icinga\Module\Icingadb\Widget\Detail\ServiceInspectionDetail;
use Icinga\Module\Icingadb\Widget\Detail\ServiceMetaInfo;
use Icinga\Module\Icingadb\Widget\ItemList\LoadMoreObjectList;
use Icinga\Module\Icingadb\Widget\ItemList\ObjectList;
use ipl\Orm\Query;
use ipl\Sql\Expression;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;
use ipl\Web\Url;
use ipl\Web\Widget\Tabs;
use Generator;

class ServiceController extends Controller
{
    use CommandActions;
    use HookActions;

    /** @var Service The service object */
    protected $service;

    public function init(): void
    {
        $name = $this->params->shiftRequired('name');
        $hostName = $this->params->shiftRequired('host.name');

        $query = Service::on($this->getDb())
            ->with([
                'state',
                'icon_image',
                'host',
                'host.state',
                'timeperiod'
            ]);
        $query
            ->setResultSetClass(VolatileStateResults::class)
            ->filter(Filter::all(
                Filter::equal('service.name', $name),
                Filter::equal('host.name', $hostName)
            ));

        if (Backend::supportsDependencies()) {
            $query->withColumns(['has_problematic_parent']);
        }

        $this->applyRestrictions($query);

        /** @var Service $service */
        $service = $query->first();
        if ($service === null) {
            throw new NotFoundError($this->translate('Service not found'));
        }

        $this->service = $service;
        $this->loadTabsForObject($service);

        $this->addControl(new ObjectHeader($service));

        $this->setTitleTab($this->getRequest()->getActionName());
        $this->setTitle(
            $this->translate('%s on %s', '<service> on <host>'),
            $service->display_name,
            $service->host->display_name
        );
    }

    public function indexAction(): void
    {
        if ($this->service->state->is_overdue) {
            $this->controls->addAttributes(['class' => 'overdue']);
        }

        $this->addControl(new ServiceMetaInfo($this->service));
        $this->addControl(new QuickActions($this->service));

        $this->addContent(new ServiceDetail($this->service));

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
                'name',
                'host.name'
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
                ->setEmptyStateMessage($paginationControl->getEmptyStateMessage())
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
            'name',
            'host.name'
        ];

        $requestParams = Url::fromRequest()->onlyWith($preserveParams)->getParams();
        $searchBar = $this->createSearchBar($nodesQuery, $preserveParams)
            ->setEditorUrl(
                Url::fromPath('icingadb/service/children-search-editor')
                    ->setParams($requestParams)
            )->setSuggestionUrl(
                Url::fromPath('icingadb/service/children-complete')
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
                ->setEmptyStateMessage($paginationControl->getEmptyStateMessage())
        );

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }

        $this->setAutorefreshInterval(10);
    }

    public function sourceAction(): void
    {
        $this->assertPermission('icingadb/object/show-source');

        $apiResult = (new CommandTransport())->send(
            (new GetObjectCommand())
                ->setObjects(new ArrayIterator([$this->service]))
        );

        if ($this->service->state->is_overdue) {
            $this->controls->addAttributes(['class' => 'overdue']);
        }

        $this->addContent(new ServiceInspectionDetail(
            $this->service,
            reset($apiResult)
        ));
    }

    public function historyAction(): Generator
    {
        $compact = $this->view->compact; // TODO: Find a less-legacy way..

        if ($this->service->state->is_overdue) {
            $this->controls->addAttributes(['class' => 'overdue']);
        }

        $db = $this->getDb();

        $history = History::on($db)->with([
            'host',
            'host.state',
            'service',
            'service.state',
            'comment',
            'downtime',
            'flapping',
            'notification',
            'acknowledgement',
            'state'
        ]);
        $history->filter(Filter::all(
            Filter::equal('history.host_id', $this->service->host_id),
            Filter::equal('history.service_id', $this->service->id)
        ));

        $before = $this->params->shift('before', time());
        $url = Url::fromRequest()->setParams(clone $this->params);
        $url->setParam('name', $this->service->name)
            ->setParam('host.name', $this->service->host->name);
        $previousTimestamp = $this->params->shift('last-entry');

        $timestampControl = $this->createTimestampControl('icingadb/service/history');
        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($history);
        $sortControl = $this->createSortControl(
            $history,
            [
                'history.event_time desc, history.event_type desc' => $this->translate('Event Time')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl, true);

        $preserveParams = [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam(),
            $viewModeSwitcher->getViewModeParam(),
            'name',
            'host.name'
        ];

        $requestParams = Url::fromRequest()->onlyWith($preserveParams)->getParams();
        $searchBar = $this->createSearchBar($history, $preserveParams)
            ->setEditorUrl(
                Url::fromPath('icingadb/service/history-search-editor')
                    ->setParams($requestParams)
            )->setSuggestionUrl(
                Url::fromPath('icingadb/service/history-complete')
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

        $history->peekAhead();

        $page = $paginationControl->getCurrentPageNumber();

        if ($page > 1 && ! $compact) {
            $previousTimestamp = null;
            $history->resetOffset();
            $history->limit($page * $limitControl->getLimit());
        }

        $history->filter(Filter::lessThanOrEqual('event_time', $before));
        $this->filter($history, $filter);

        yield $this->export($history);

        $this->addControl($timestampControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($searchBar);

        $historyList = (new LoadMoreObjectList($history->execute(), $previousTimestamp, $this->useRelativeTimestamps))
            ->setViewMode($viewModeSwitcher->getViewMode())
            ->setPageSize($limitControl->getLimit())
            ->setLoadMoreUrl($url->setParam('before', $before)->setFilter($filter));

        if ($compact) {
            $historyList->setPageNumber($page);
        }

        if ($compact && $page > 1) {
            $this->document->addFrom($historyList);
        } else {
            $this->addContent($historyList);
        }

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }
    }

    public function completeAction(): void
    {
        $suggestions = (new ObjectSuggestions())
            ->setModel(DependencyNode::class)
            ->onlyWithCustomVarSources(['host', 'service', 'hostgroup', 'servicegroup'])
            ->setBaseFilter(Filter::equal("child.service.id", $this->service->id))
            ->forRequest($this->getServerRequest());

        $this->getDocument()->add($suggestions);
    }

    public function childrenCompleteAction(): void
    {
        $suggestions = (new ObjectSuggestions())
            ->setModel(DependencyNode::class)
            ->onlyWithCustomVarSources(['host', 'service', 'hostgroup', 'servicegroup'])
            ->setBaseFilter(Filter::equal("parent.service.id", $this->service->id))
            ->forRequest($this->getServerRequest());

        $this->getDocument()->add($suggestions);
    }

    public function historyCompleteAction(): void
    {
        $suggestions = (new ObjectSuggestions())
            ->setModel(History::class)
            ->setBaseFilter(Filter::equal('service.id', $this->service->id))
            ->forRequest($this->getServerRequest());

        $this->getDocument()->addHtml($suggestions);
    }

    public function searchEditorAction(): void
    {
        $editor = $this->createSearchEditor(
            DependencyNode::on($this->getDb()),
            Url::fromPath(
                'icingadb/service/parents',
                ['name' => $this->service->name, 'host.name' => $this->service->host->name]
            ),
            [
                LimitControl::DEFAULT_LIMIT_PARAM,
                SortControl::DEFAULT_SORT_PARAM,
                ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM,
                'name',
                'host.name'
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
            'name',
            'host.name'
        ];

        $editor = $this->createSearchEditor(
            DependencyNode::on($this->getDb()),
            Url::fromPath(
                'icingadb/service/children',
                ['name' => $this->service->name, 'host.name' => $this->service->host->name]
            ),
            $preserveParams
        );

        $editor->setSuggestionUrl(
            Url::fromPath('icingadb/service/children-complete')
                ->setParams(Url::fromRequest()->onlyWith($preserveParams)->getParams())
        );

        $this->getDocument()->add($editor);
        $this->setTitle($this->translate('Adjust Filter'));
    }

    public function historySearchEditorAction(): void
    {
        $preserveParams = [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM,
            'name',
            'host.name'
        ];
        $editor = $this->createSearchEditor(
            History::on($this->getDb()),
            Url::fromPath('icingadb/service/history', ['name' => $this->service->name]),
            $preserveParams
        );
        $editor->setSuggestionUrl(
            Url::fromPath('icingadb/service/history-complete')
                ->setParams(Url::fromRequest()->onlyWith($preserveParams)->getParams())
        );

        $this->getDocument()->addHtml($editor);
        $this->setTitle($this->translate('Adjust Filter'));
    }

    /**
     * Fetch the dependency nodes of the current service
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
            ->filter(Filter::equal(
                sprintf('%s.service.id', $parents ? 'child' : 'parent'),
                $this->service->id
            ))
            ->setResultSetClass(VolatileStateResults::class);

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
                    Filter::equal('service_id', $this->service->id),
                    Filter::equal('host_id', $this->service->host_id)
                ))
                ->disableDefaultSort()
                ->first() !== null;
        }

        $tabs = $this->getTabs()
            ->add('index', [
                'label'  => $this->translate('Service'),
                'url'    => Links::service($this->service, $this->service->host)
            ])
            ->add('history', [
                'label'  => $this->translate('History'),
                'url'    => ServiceLinks::history($this->service, $this->service->host)
            ]);

        if ($hasDependencyNode) {
            $tabs->add('parents', [
                'label'  => $this->translate('Parents'),
                'url'    => Url::fromPath(
                    'icingadb/service/parents',
                    ['name' => $this->service->name, 'host.name' => $this->service->host->name]
                )
            ])->add('children', [
                'label'  => $this->translate('Children'),
                'url'    => Url::fromPath(
                    'icingadb/service/children',
                    ['name' => $this->service->name, 'host.name' => $this->service->host->name]
                )
            ]);
        }

        if ($this->hasPermission('icingadb/object/show-source')) {
            $tabs->add('source', [
                'label' => $this->translate('Source'),
                'url'   => Links::serviceSource($this->service, $this->service->host)
            ]);
        }

        foreach ($this->loadAdditionalTabs() as $name => $tab) {
            $tabs->add($name, $tab + ['urlParams' => [
                'name'      => $this->service->name,
                'host.name' => $this->service->host->name
            ]]);
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
        return [$this->service];
    }

    protected function getCommandTargetsUrl(): Url
    {
        return Links::service($this->service, $this->service->host);
    }

    protected function getDefaultTabControls(): array
    {
        return [new ObjectHeader($this->service)];
    }
}
