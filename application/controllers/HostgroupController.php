<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Generator;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Hostgroupsummary;
use Icinga\Module\Icingadb\Redis\VolatileStateResults;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Control\ViewModeSwitcher;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\HostList;
use Icinga\Module\Icingadb\Widget\ItemTable\HostgroupTableRow;
use ipl\Html\Html;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;
use ipl\Web\Url;

class HostgroupController extends Controller
{
    /** @var string */
    protected $hostgroupName;

    public function init()
    {
        $this->assertRouteAccess('hostgroups');
        $this->hostgroupName = $this->params->shiftRequired('name');
    }

    /**
     * Fetch the host group object
     *
     * @return Hostgroupsummary
     */
    protected function fetchHostgroup(): Hostgroupsummary
    {
        $query = Hostgroupsummary::on($this->getDb());

        foreach ($query->getUnions() as $unionPart) {
            $unionPart->filter(Filter::equal('hostgroup.name', $this->hostgroupName));
        }

        $this->applyRestrictions($query);

        /** @var Hostgroupsummary $hostgroup */
        $hostgroup = $query->first();
        if ($hostgroup === null) {
            $this->httpNotFound(t('Host group not found'));
        }

        return $hostgroup;
    }

    public function indexAction(): Generator
    {
        $db = $this->getDb();
        $hostgroup = $this->fetchHostgroup();

        $hosts = Host::on($db)->with(['state', 'state.last_comment', 'icon_image']);
        $hosts
            ->setResultSetClass(VolatileStateResults::class)
            ->filter(Filter::equal('hostgroup.id', $hostgroup->id));
        $this->applyRestrictions($hosts);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($hosts);
        $sortControl = $this->createSortControl(
            $hosts,
            [
                'host.display_name'                                          => t('Name'),
                'host.state.severity desc,host.state.last_state_change desc' => t('Severity'),
                'host.state.soft_state'                                      => t('Current State'),
                'host.state.last_state_change desc'                          => t('Last State Change')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);

        $searchBar = $this->createSearchBar($hosts, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam(),
            $viewModeSwitcher->getViewModeParam(),
            'name'
        ])->setSuggestionUrl(Url::fromPath(
            'icingadb/hostgroup/complete',
            [
                'name' => $this->hostgroupName,
                '_disableLayout' => true,
                'showCompact' => true
            ]
        ));

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

        $hosts->filter($filter);

        yield $this->export($hosts);

        $hostList = (new HostList($hosts->execute()))
            ->setViewMode($viewModeSwitcher->getViewMode());

        // ICINGAWEB_EXPORT_FORMAT is not set yet and $this->format is inaccessible, yeah...
        if ($this->getRequest()->getParam('format') === 'pdf') {
            $this->addContent(new HostgroupTableRow($hostgroup));
            $this->addContent(Html::tag('h2', null, t('Hosts')));
        } else {
            $this->addControl(new HostgroupTableRow($hostgroup));
        }

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($searchBar);
        $continueWith = $this->createContinueWith(Links::hostsDetails(), $searchBar);

        $this->addContent($hostList);

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate($continueWith);
        }

        $this->addTitleTab(t('Host Group'));
        $this->setTitle($hostgroup->display_name);
        $this->setAutorefreshInterval(10);
    }

    public function completeAction(): void
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(Host::class);
        $suggestions->setBaseFilter(Filter::equal('hostgroup.name', $this->hostgroupName));
        $suggestions->forRequest($this->getServerRequest());
        $this->getDocument()->add($suggestions);
    }

    public function searchEditorAction(): void
    {
        $editor = $this->createSearchEditor(Host::on($this->getDb()), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM,
            'name'
        ]);

        $this->getDocument()->add($editor);
        $this->setTitle(t('Adjust Filter'));
    }
}
