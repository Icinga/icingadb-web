<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterExpression;
use Icinga\Module\Icingadb\Common\CommandActions;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Compat\FeatureStatus;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ContinueWith;
use Icinga\Module\Icingadb\Widget\Detail\MultiselectQuickActions;
use Icinga\Module\Icingadb\Widget\Detail\ObjectsDetail;
use Icinga\Module\Icingadb\Widget\ServiceList;
use Icinga\Module\Icingadb\Widget\ServiceStatusBar;
use Icinga\Module\Icingadb\Widget\ShowMore;
use ipl\Orm\Compat\FilterProcessor;

class ServicesController extends Controller
{
    use CommandActions;

    public function indexAction()
    {
        $this->setTitle($this->translate('Services'));

        $db = $this->getDb();

        $services = Service::on($db)->with([
            'state',
            'host',
            'host.state'
        ]);

        $summary = null;
        if (! $this->view->compact) {
            $summary = ServicestateSummary::on($db)->with('state');
        }

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($services);
        $sortControl = $this->createSortControl(
            $services,
            [
                'service.display_name, host.display_name' => $this->translate('Name'),
                'service.state.severity desc'             => $this->translate('Severity'),
                'service.state.soft_state'                => $this->translate('Current State'),
                'service.state.last_state_change desc'    => $this->translate('Last State Change'),
                'host.display_name, service.display_name' => $this->translate('Host')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher();
        $filterControl = $this->createFilterControl($services);

        $this->filter($services);
        if (isset($summary)) {
            $this->filter($summary);
            yield $this->export($services, $summary);
        } else {
            yield $this->export($services);
        }

        $serviceList = (new ServiceList($services))
            ->setViewMode($viewModeSwitcher->getViewMode());

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($filterControl);
        $this->addControl(new ContinueWith($this->getFilter(), Links::servicesDetails()));

        $this->addContent($serviceList);

        if (isset($summary)) {
            $this->addFooter(
                (new ServiceStatusBar($summary->first()))->setBaseFilter($this->getFilter())
            );
        }

        $this->setAutorefreshInterval(10);
    }

    public function detailsAction()
    {
        $this->setTitle($this->translate('Services'));

        $db = $this->getDb();

        $services = Service::on($db)->with('state');
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
        $this->filter($comments);
        $summary->comments_total = $comments->count();

        $this->addControl(
            (new ServiceList($results))
                ->setViewMode('minimal')
        );
        $this->addControl(new ShowMore(
            $results,
            Links::services()->setQueryString($this->getFilter()->toQueryString()),
            sprintf($this->translate('Show all %d services'), $services->count())
        ));
        $this->addControl(
            (new MultiselectQuickActions('service', $services, $summary))
                ->setBaseFilter($this->getFilter())
        );

        $this->addContent(
            (new ObjectsDetail('service', $services, $summary))
                ->setBaseFilter($this->getFilter())
        );
    }

    public function fetchCommandTargets()
    {
        $db = $this->getDb();

        $services = Service::on($db)->with([
            'state',
            'host',
            'host.state'
        ]);

        switch ($this->getRequest()->getActionName()) {
            case 'acknowledge':
                FilterProcessor::apply(
                    Filter::matchAll([
                        new FilterExpression('state.is_problem', '=', 'y'),
                        new FilterExpression('state.is_acknowledged', '=', 'n')
                    ]),
                    $services
                );

                break;
        }

        $this->filter($services);

        return $services;
    }

    public function getCommandTargetsUrl()
    {
        return Links::servicesDetails()->setQueryString($this->getFilter()->toQueryString());
    }

    protected function getFeatureStatus()
    {
        return new FeatureStatus('service', ServicestateSummary::on($this->getDb())->with(['state'])->first());
    }
}
