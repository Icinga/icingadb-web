<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Utils;
use Icinga\Module\Icingadb\Common\CommandActions;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\HoststateSummary;
use Icinga\Module\Icingadb\Redis\VolatileStateResults;
use Icinga\Module\Icingadb\Util\FeatureStatus;
use Icinga\Module\Icingadb\Web\Control\ColumnChooser;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Control\TabularViewModeSwitcher;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\MultiselectQuickActions;
use Icinga\Module\Icingadb\Widget\Detail\ObjectsDetail;
use Icinga\Module\Icingadb\Widget\HostStatusBar;
use Icinga\Module\Icingadb\Widget\ItemList\ObjectList;
use Icinga\Module\Icingadb\Widget\ItemTable\HostItemTable;
use Icinga\Module\Icingadb\Web\Control\ViewModeSwitcher;
use Icinga\Module\Icingadb\Widget\ShowMore;
use ipl\Orm\Query;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;
use ipl\Web\Url;

class HostsController extends Controller
{
    use CommandActions;

    public function indexAction()
    {
        $this->addTitleTab(t('Hosts'));
        $compact = $this->view->compact;

        $db = $this->getDb();

        $hosts = Host::on($db)->with(['state', 'icon_image', 'state.last_comment']);
        $hosts->getWith()['host.state']->setJoinType('INNER');
        $hosts->setResultSetClass(VolatileStateResults::class);

        $this->handleSearchRequest($hosts, ['address', 'address6']);

        $summary = null;
        if (! $compact) {
            $summary = HoststateSummary::on($db);
        }

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($hosts);
        $sortControl = $this->createSortControl(
            $hosts,
            [
                'host.display_name'                                          => t('Name'),
                'host.state.severity desc,host.state.last_state_change desc' => t('Severity'),
                'host.state.soft_state'                                      => t('Current State'),
                'host.state.last_state_change desc'                          => t('Last State Change')
            ],
            ['host.state.severity DESC', 'host.state.last_state_change DESC']
        );
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);
        $columns = $this->createColumnControl($hosts, $viewModeSwitcher);

        $searchBar = $this->createSearchBar($hosts, [
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

        $hosts->peekAhead($compact);

        $this->filter($hosts, $filter);
        if (! $compact) {
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

        $results = $hosts->execute();

        $continueWith = $this->createContinueWith(Links::hostsDetails(), $searchBar, $results->hasResult());
        if ($viewModeSwitcher->getViewMode() === 'tabular') {
            $hostList = (new HostItemTable($results, HostItemTable::applyColumnMetaData($hosts, $columns)))
                ->setSort($sortControl->getSort());
        } else {
            $hostList = (new ObjectList($results))
                ->setViewMode($viewModeSwitcher->getViewMode());
        }

        $hostList->setEmptyStateMessage($paginationControl->getEmptyStateMessage());

        $this->addContent($hostList);

        if ($compact) {
            $this->addContent(
                (new ShowMore($results, Url::fromRequest()->without(['showCompact', 'limit', 'view'])))
                    ->setBaseTarget('_next')
                    ->setAttribute('title', sprintf(
                        t('Show all %d hosts'),
                        $hosts->count()
                    ))
            );
        } else {
            /** @var HoststateSummary $hostsSummary */
            $hostsSummary = $summary->first();
            $this->addFooter((new HostStatusBar($hostsSummary))->setBaseFilter($filter));
        }

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate($continueWith);
        }

        $this->setAutorefreshInterval(10);
    }

    public function detailsAction()
    {
        $this->addTitleTab(t('Hosts'));

        $db = $this->getDb();

        $hosts = Host::on($db)->with(['state', 'icon_image']);
        $hosts->setResultSetClass(VolatileStateResults::class);
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
        $comments->filter(Filter::equal('comment.object_type', 'host'));
        $this->filter($comments);
        $summary->comments_total = $comments->count();

        $this->addControl(
            (new ObjectList($results))
                ->setViewMode('minimal')
                ->setDetailActionsDisabled()
        );
        $this->addControl(new ShowMore(
            $results,
            Links::hosts()->setFilter($this->getFilter()),
            sprintf(t('Show all %d hosts'), $hosts->count())
        ));
        $this->addControl(
            (new MultiselectQuickActions('host', $summary))
                ->setBaseFilter($this->getFilter())
        );

        $this->addContent(
            (new ObjectsDetail('host', $summary, $hosts))
                ->setBaseFilter($this->getFilter())
        );
    }

    public function completeAction()
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(Host::class);
        $request = clone ServerRequest::fromGlobals();
        $requestData = json_decode($request->getBody()->read(8192), true);
        if (! array_key_exists('type', $requestData['term'])) {
            $requestData['term']['type'] = 'column';
        }

        $request = $request->withBody(Utils::streamFor(json_encode($requestData)));
        $suggestions->forRequest($request);
        $this->getDocument()->add($suggestions);
    }

    public function searchEditorAction()
    {
        $editor = $this->createSearchEditor(Host::on($this->getDb()), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM,
            'columns'
        ]);

        $this->getDocument()->add($editor);
        $this->setTitle(t('Adjust Filter'));
    }

    public function columnControlAction()
    {
        $this->addTitleTab($this->translate('Select Columns'));
        $this->addContent(
            (new ColumnChooser(Url::fromPath('icingadb/hosts/complete'), Host::class))
                ->setAction((string) Url::fromRequest())
                ->on(ColumnChooser::ON_SENT, function (ColumnChooser $form) {
                    if ($form->hasBeenSubmitted()) {
                        $url = Url::fromPath('icingadb/hosts');
                        $url->setParam('columns', $form->getValue('columns', ''));
                        $this->redirectNow($url);
                    } else {
                        foreach ($form->getPartUpdates() as $update) {
                            if (! is_array($update)) {
                                $update = [$update];
                            }

                            $this->addPart(...$update);
                        }
                    }
                })->handleRequest($this->getServerRequest())
        );
    }

    protected function fetchCommandTargets(): Query
    {
        $db = $this->getDb();

        $hosts = Host::on($db)->with('state');
        $hosts->setResultSetClass(VolatileStateResults::class);

        switch ($this->getRequest()->getActionName()) {
            case 'acknowledge':
                $hosts->filter(Filter::equal('state.is_problem', 'y'))
                    ->filter(Filter::equal('state.is_acknowledged', 'n'));

                break;
        }

        $this->filter($hosts);

        return $hosts;
    }

    protected function getCommandTargetsUrl(): Url
    {
        return Links::hostsDetails()->setFilter($this->getFilter());
    }

    protected function getFeatureStatus()
    {
        $summary = HoststateSummary::on($this->getDb());
        $this->filter($summary);

        return new FeatureStatus('host', $summary->first());
    }

    protected function getViewModeSwitcherInstance(): ViewModeSwitcher
    {
        return new TabularViewModeSwitcher();
    }
}
