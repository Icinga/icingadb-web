<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Common\CommandActions;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Data\DependencyNodes;
use Icinga\Module\Icingadb\Model\DependencyNode;
use Icinga\Module\Icingadb\Model\RedundancyGroup;
use Icinga\Module\Icingadb\Model\RedundancyGroupSummary;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Control\ViewModeSwitcher;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\DependencyNodeStatistics;
use Icinga\Module\Icingadb\Widget\Detail\MultiselectQuickActions;
use Icinga\Module\Icingadb\Widget\Detail\RedundancyGroupDetail;
use Icinga\Module\Icingadb\Widget\ItemList\DependencyNodeList;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Orm\Query;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;
use ipl\Web\Url;
use ipl\Web\Widget\Tabs;

class RedundancygroupController extends Controller
{
    use CommandActions;

    /** @var string */
    protected $groupId;

    /** @var RedundancyGroup */
    protected $group;

    public function init(): void
    {
        // in case of quick actions, param id is not given
        $groupId = $this->params->shift('child.redundancy_group.id');
        if ($groupId === null) {
            $groupId = $this->params->shiftRequired('id');
        }

        $this->groupId = $groupId;

        $query = RedundancyGroup::on($this->getDb())
            ->with(['state'])
            ->filter(Filter::equal('id', $this->groupId));

        $this->applyRestrictions($query);

        $this->group = $query->first();

        if ($this->group === null) {
            throw new NotFoundError(t('Redundancy Group not found'));
        }

        $this->setTitleTab($this->getRequest()->getActionName());
        $this->setTitle($this->group->display_name);

        $this->addControl(new HtmlElement('div', null, Text::create($this->group->display_name)));
        $this->addFooter(
            new DependencyNodeStatistics(
                RedundancyGroupSummary::on($this->getDb())
                    ->filter(Filter::equal('id', $this->groupId))
                    ->first()
            )
        );
    }

    public function indexAction(): void
    {
        $summary = RedundancyGroupSummary::on($this->getDb())
            ->filter(Filter::equal('id', $this->groupId));

        $this->filter($summary);

        // The base filter is required to fetch the correct objects for MultiselectQuickActions::isGrantedOnType() check
        $this->addControl(
            (new MultiselectQuickActions('dependency_node', $summary->first()))
                ->setBaseFilter(Filter::equal('child.redundancy_group.id', $this->groupId))
                ->setAllowToProcessCheckResults(false)
                ->setColumnPrefix('nodes')
                ->setUrlPath('icingadb/redundancygroup')
        );

        $this->addContent(new RedundancyGroupDetail($this->group));
    }

    public function membersAction(): \Generator
    {
        $nodesQuery = $this->fetchNodes(true);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($nodesQuery);
        $sortControl = $this->createSortControl(
            $nodesQuery,
            [
                'name'                                  => t('Name'),
                'severity desc, last_state_change desc' => t('Severity'),
                'state'                                 => t('Current State'),
                'last_state_change desc'                => t('Last State Change')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);

        $searchBar = $this->createSearchBar($nodesQuery,
            Links::redundancyGroupMembers($this->group),
            [
                $limitControl->getLimitParam(),
                $sortControl->getSortParam(),
                $viewModeSwitcher->getViewModeParam(),
                'id'
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
            (new DependencyNodeList($nodesQuery->execute()))
                ->setViewMode($viewModeSwitcher->getViewMode())
        );

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }

        $this->setAutorefreshInterval(10);
    }

    public function childrenAction()
    {
        $nodesQuery = $this->fetchNodes();

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($nodesQuery);
        $sortControl = $this->createSortControl(
            $nodesQuery,
            [
                'name'                                  => t('Name'),
                'severity desc, last_state_change desc' => t('Severity'),
                'state'                                 => t('Current State'),
                'last_state_change desc'                => t('Last State Change')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);

        $searchBar = $this->createSearchBar(
            $nodesQuery,
            Links::redundancyGroupChildren($this->group),
            [
                $limitControl->getLimitParam(),
                $sortControl->getSortParam(),
                $viewModeSwitcher->getViewModeParam(),
                'id'
            ]
        );

        $searchBar->getSuggestionUrl()->setParam('isChildrenTab');
        $searchBar->getEditorUrl()->setParam('isChildrenTab');

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
            (new DependencyNodeList($nodesQuery->execute()))
                ->setViewMode($viewModeSwitcher->getViewMode())
        );

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }

        $this->setAutorefreshInterval(10);
    }

    public function completeAction(): void
    {
        $isChildrenTab = $this->params->shift('isChildrenTab');
        $column = $isChildrenTab ? 'parent' : 'child';

        $suggestions = (new ObjectSuggestions())
            ->setModel(DependencyNode::class)
            ->setBaseFilter(Filter::equal("$column.redundancy_group.id", $this->groupId))
            ->forRequest($this->getServerRequest());

        $this->getDocument()->add($suggestions);
    }

    public function searchEditorAction(): void
    {
        $isChildrenTab = $this->params->shift('isChildrenTab');
        $redirectUrl = $isChildrenTab
            ? Links::redundancyGroupChildren($this->group)
            : Links::redundancyGroupMembers($this->group);

        $editor = $this->createSearchEditor(DependencyNode::on($this->getDb()),
            $redirectUrl,
            [
                LimitControl::DEFAULT_LIMIT_PARAM,
                SortControl::DEFAULT_SORT_PARAM,
                ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM,
                'id'
            ]
        );

        if ($isChildrenTab) {
            $editor->getSuggestionUrl()->setParam('isChildrenTab');
        }

        $this->getDocument()->add($editor);
        $this->setTitle(t('Adjust Filter'));
    }

    protected function createTabs(): Tabs
    {
        $tabs = $this->getTabs()
            ->add('index', [
                'label'  => t('Redundancy Group'),
                'url'    => Links::redundancyGroup($this->group)
            ])
            ->add('members', [
                'label'  => t('Members'),
                'url'    => Links::redundancyGroupMembers($this->group)
            ])
            ->add('children', [
                'label'  => t('Children'),
                'url'    => Links::redundancyGroupChildren($this->group)
            ]);

        return $tabs;
    }

    protected function setTitleTab(string $name): void
    {
        $tab = $this->createTabs()->get($name);

        if ($tab !== null) {
            $this->getTabs()->activate($name);
        }
    }

    /**
     * Fetch the nodes for the current group
     *
     * @param bool $fetchParents Whether to fetch the parents or the children
     *
     * @return Query
     */
    private function fetchNodes(bool $fetchParents = false): Query
    {
        $filterColumn = sprintf(
            '%s.redundancy_group.id',
            $fetchParents ? 'child' : 'parent'
        );

        return DependencyNode::on($this->getDb())
            ->with([
                'host',
                'host.state',
                'service',
                'service.state',
                'service.host',
                'service.host.state'
            ])
            ->filter(Filter::equal($filterColumn, $this->groupId));
    }

    protected function fetchCommandTargets()
    {
        $filter = Filter::all(Filter::equal('child.redundancy_group.id', $this->groupId));

        if ($this->getRequest()->getActionName() === 'acknowledge') {
            $filter->add(
                Filter::any(
                    Filter::all(
                        Filter::unlike('child.service.id', '*'),
                        Filter::equal('host.state.is_problem', 'y'),
                        Filter::equal('host.state.is_acknowledged', 'n')
                    ),
                    Filter::all(
                        Filter::equal('service.state.is_problem', 'y'),
                        Filter::equal('service.state.is_acknowledged', 'n')
                    )
                )
            );
        }

        return new DependencyNodes($filter);
    }

    protected function getCommandTargetsUrl(): Url
    {
        return Links::redundancyGroup($this->group);
    }

    public function processCheckresultAction(): void
    {
        $this->httpBadRequest('Check result submission not implemented yet');
    }
}
