<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Common\CommandActions;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\HoststateSummary;
use Icinga\Module\Icingadb\Util\FeatureStatus;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ContinueWith;
use Icinga\Module\Icingadb\Widget\Detail\MultiselectQuickActions;
use Icinga\Module\Icingadb\Widget\Detail\ObjectsDetail;
use Icinga\Module\Icingadb\Widget\HostList;
use Icinga\Module\Icingadb\Widget\HostStatusBar;
use Icinga\Module\Icingadb\Widget\ShowMore;
use ipl\Orm\Compat\FilterProcessor;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;

class HostsController extends Controller
{
    use CommandActions;

    public function indexAction()
    {
        $this->setTitle(t('Hosts'));
        $compact = $this->view->compact;

        $db = $this->getDb();

        $hosts = Host::on($db)->with('state');

        $this->handleSearchRequest($hosts);

        $summary = null;
        if (! $compact) {
            $summary = HoststateSummary::on($db)->with('state');
        }

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($hosts);
        $sortControl = $this->createSortControl(
            $hosts,
            [
                'host.display_name'                 => t('Name'),
                'host.state.severity desc'          => t('Severity'),
                'host.state.soft_state'             => t('Current State'),
                'host.state.last_state_change desc' => t('Last State Change')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher();
        $searchBar = $this->createSearchBar($hosts, [
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

        $hosts->peekAhead($compact);

        $this->filter($hosts, $filter);
        if (isset($summary)) {
            $this->filter($summary, $filter);
            yield $this->export($hosts, $summary);
        } else {
            yield $this->export($hosts);
        }

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($searchBar);
        $this->addControl(new ContinueWith($this->getFilter(), Links::hostsDetails()));

        $results = $hosts->execute();
        $hostList = (new HostList($results))
            ->setViewMode($viewModeSwitcher->getViewMode());

        $this->addContent($hostList);

        if ($compact) {
            $this->addContent(
                (new ShowMore($results, Url::fromRequest()->without(['showCompact', 'limit'])))
                    ->setAttribute('data-base-target', '_next')
                    ->setAttribute('title', sprintf(
                        t('Show all %d hosts'),
                        $hosts->count()
                    ))
            );
        } else {
            $this->addFooter((new HostStatusBar($summary->first()))->setBaseFilter($filter));
        }

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $viewModeSwitcher->setUrl($searchBar->getRedirectUrl());
            $this->sendMultipartUpdate($viewModeSwitcher);
        }

        $this->setAutorefreshInterval(10);
    }

    public function detailsAction()
    {
        $this->setTitle(t('Hosts'));

        $db = $this->getDb();

        $hosts = Host::on($db)->with('state');
        $summary = HoststateSummary::on($db)->with(['state']);

        $this->filter($hosts);
        $this->filter($summary);

        $hosts->limit(3);
        $hosts->peekAhead();

        yield $this->export($hosts, $summary);

        $results = $hosts->execute();
        $summary = $summary->first();

        $downtimes = Host::on($db)->with(['downtime']);
        $downtimes->getWith()['host.downtime']->setJoinType('INNER');
        $this->filter($downtimes);
        $summary->downtimes_total = $downtimes->count();

        $comments = Host::on($db)->with(['comment']);
        $comments->getWith()['host.comment']->setJoinType('INNER');
        // TODO: This should be automatically done by the model/resolver and added as ON condition
        FilterProcessor::apply(Filter::equal('comment.object_type', 'host'), $comments);
        $this->filter($comments);
        $summary->comments_total = $comments->count();

        $this->addControl(
            (new HostList($results))
                ->setViewMode('minimal')
        );
        $this->addControl(new ShowMore(
            $results,
            Links::hosts()->setQueryString(QueryString::render($this->getFilter())),
            sprintf(t('Show all %d hosts'), $hosts->count())
        ));
        $this->addControl(
            (new MultiselectQuickActions('host', $summary))
                ->setBaseFilter($this->getFilter())
        );

        $this->addContent(
            (new ObjectsDetail('host', $summary))
                ->setBaseFilter($this->getFilter())
        );
    }

    public function completeAction()
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(Host::class);
        $suggestions->forRequest(ServerRequest::fromGlobals());
        $this->getDocument()->add($suggestions);
    }

    public function fetchCommandTargets()
    {
        $db = $this->getDb();

        $hosts = Host::on($db)->with('state');

        switch ($this->getRequest()->getActionName()) {
            case 'acknowledge':
                FilterProcessor::apply(
                    Filter::all([
                        Filter::equal('state.is_problem', 'y'),
                        Filter::equal('state.is_acknowledged', 'n')
                    ]),
                    $hosts
                );

                break;
        }

        $this->filter($hosts);

        return $hosts;
    }

    public function getCommandTargetsUrl()
    {
        return Links::hostsDetails()->setQueryString(QueryString::render($this->getFilter()));
    }

    protected function getFeatureStatus()
    {
        $summary = HoststateSummary::on($this->getDb())->with(['state']);
        $this->filter($summary);

        return new FeatureStatus('host', $summary->first());
    }
}
