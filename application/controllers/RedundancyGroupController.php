<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Model\DependencyNode;
use Icinga\Module\Icingadb\Model\RedundancyGroup;
use Icinga\Module\Icingadb\Model\RedundancyGroupSummary;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Control\ViewModeSwitcher;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\DependencyNodeStatistics;
use Icinga\Module\Icingadb\Widget\ItemList\DependencyNodeList;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;

class RedundancyGroupController extends Controller
{
    /** @var string */
    protected $groupId;

    /** @var RedundancyGroup */
    protected $group;

    public function init(): void
    {
        $this->addTitleTab(t('Redundancy Group'));

        $this->groupId = $this->params->shiftRequired('id');

        $this->group = RedundancyGroup::on($this->getDb())
            ->with(['state'])
            ->filter(Filter::equal('id', $this->groupId))
            ->first();

        if ($this->group === null) {
            throw new NotFoundError(t('Redundancy Group not found'));
        }
    }

    public function indexAction()
    {
        $membersQuery = DependencyNode::on($this->getDb())
            ->with([
                'host',
                'host.state',
                'service',
                'service.state',
                'service.host',
                'service.host.state'
            ])
            ->filter(Filter::equal('child.redundancy_group.id', $this->groupId));

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($membersQuery);
        $sortControl = $this->createSortControl(
            $membersQuery,
            [
                'name'                                  => t('Name'),
                'severity desc, last_state_change desc' => t('Severity'),
                'state'                                 => t('Current State'),
                'last_state_change desc'                => t('Last State Change')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);

        $searchBar = $this->createSearchBar($membersQuery, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam(),
            $viewModeSwitcher->getViewModeParam(),
            'id'
        ]);
        $searchBar->getSuggestionUrl()->addParams(['id' => $this->groupId]);

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

        $membersQuery->filter($filter);

        yield $this->export($membersQuery);

        $this->addControl(new HtmlElement('div', null, Text::create($this->group->display_name)));

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($searchBar);

        $this->addContent(
            (new DependencyNodeList($membersQuery->execute()))
                ->setViewMode($viewModeSwitcher->getViewMode())
        );

        $this->addFooter(
            new DependencyNodeStatistics(
                RedundancyGroupSummary::on($this->getDb())
                    ->filter(Filter::equal('id', $this->groupId))
                    ->first()
            )
        );

        $this->setAutorefreshInterval(10);
    }

    public function completeAction(): void
    {
        $suggestions = (new ObjectSuggestions())
            ->setModel(DependencyNode::class)
            ->setBaseFilter(Filter::equal('child.redundancy_group.id', $this->groupId))
            ->forRequest($this->getServerRequest());

        $this->getDocument()->add($suggestions);
    }

    public function searchEditorAction(): void
    {
        $editor = $this->createSearchEditor(DependencyNode::on($this->getDb()), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM,
            'id'
        ]);

        if ($editor->getSuggestionUrl()) {
            $editor->getSuggestionUrl()->addParams(['id' => $this->groupId]);
        }

        $this->getDocument()->add($editor);
        $this->setTitle(t('Adjust Filter'));
    }
}