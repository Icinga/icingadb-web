<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Generator;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Model\ServicegroupSummary;
use Icinga\Module\Icingadb\Redis\VolatileStateResults;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Control\ViewModeSwitcher;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\ObjectHeader;
use Icinga\Module\Icingadb\Widget\ItemList\ObjectList;
use ipl\Html\Html;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;

class ServicegroupController extends Controller
{
    /** @var string */
    protected $servicegroupName;

    public function init()
    {
        $this->assertRouteAccess('servicegroups');
        $this->servicegroupName = $this->params->shiftRequired('name');
    }

    /**
     * Fetch the service group object
     *
     * @return ServicegroupSummary
     */
    protected function fetchServicegroup(): ServicegroupSummary
    {
        $query = ServicegroupSummary::on($this->getDb());

        foreach ($query->getUnions() as $unionPart) {
            $unionPart->filter(Filter::equal('servicegroup.name', $this->servicegroupName));
        }

        $this->applyRestrictions($query);

        /** @var ServicegroupSummary $servicegroup */
        $servicegroup = $query->first();
        if ($servicegroup === null) {
            $this->httpNotFound(t('Service group not found'));
        }

        return $servicegroup;
    }

    public function indexAction(): Generator
    {
        $db = $this->getDb();
        $servicegroup = $this->fetchServicegroup();

        $services = Service::on($db)->with([
            'state',
            'state.last_comment',
            'icon_image',
            'host',
            'host.state'
        ]);
        $services
            ->setResultSetClass(VolatileStateResults::class)
            ->filter(Filter::equal('servicegroup.id', $servicegroup->id));

        $this->applyRestrictions($services);

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
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);

        $searchBar = $this->createSearchBar($services, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam(),
            $viewModeSwitcher->getViewModeParam(),
            'name'
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

        $services->filter($filter);

        yield $this->export($services);

        $serviceList = (new ObjectList($services))
            ->setViewMode($viewModeSwitcher->getViewMode());

        // ICINGAWEB_EXPORT_FORMAT is not set yet and $this->format is inaccessible, yeah...
        if ($this->getRequest()->getParam('format') === 'pdf') {
            $this->addContent(new ObjectHeader($servicegroup));
            $this->addContent(Html::tag('h2', null, t('Services')));
        } else {
            $this->addControl(new ObjectHeader($servicegroup));
        }

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($searchBar);
        $continueWith = $this->createContinueWith(Links::servicesDetails(), $searchBar);

        $this->addContent($serviceList);

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate($continueWith);
        }

        $this->addTitleTab(t('Service Group'));
        $this->setTitle($servicegroup->display_name);
        $this->setAutorefreshInterval(10);
    }

    public function completeAction(): void
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(Service::class);
        $suggestions->setBaseFilter(Filter::equal('servicegroup.name', $this->servicegroupName));
        $suggestions->forRequest($this->getServerRequest());
        $this->getDocument()->add($suggestions);
    }

    public function searchEditorAction(): void
    {
        $editor = $this->createSearchEditor(Service::on($this->getDb()), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM,
            'name'
        ]);

        $this->getDocument()->add($editor);
        $this->setTitle(t('Adjust Filter'));
    }
}
