<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Common\CommandActions;
use Icinga\Module\Icingadb\Common\HostLinks;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\HostDetail;
use Icinga\Module\Icingadb\Widget\Detail\QuickActions;
use Icinga\Module\Icingadb\Widget\DowntimeList;
use Icinga\Module\Icingadb\Widget\HostList;
use Icinga\Module\Icingadb\Widget\ItemList\CommentList;
use Icinga\Module\Icingadb\Widget\ItemList\HistoryList;
use Icinga\Module\Icingadb\Widget\ServiceList;
use Icinga\Module\Icingadb\Widget\ShowMore;

class HostController extends Controller
{
    use CommandActions;

    /** @var Host The host object */
    protected $host;

    public function init()
    {
        $name = $this->params->shiftRequired('name');

        $query = Host::on($this->getDb())->with('state');
        $query->getSelectBase()
            ->where(['host.name = ?' => $name]);

        $this->applyMonitoringRestriction($query);

        /** @var Host $host */
        $host = $query->first();
        if ($host === null) {
            throw new NotFoundError($this->translate('Host not found'));
        }

        $this->host = $host;

        $this->setTitleTab($this->getRequest()->getActionName());
    }

    public function indexAction()
    {
        $serviceSummary = ServicestateSummary::on($this->getDb())->with('state');
        $serviceSummary->getSelectBase()
            ->where(['host_id = ?' => $this->host->id]);

        if ($this->host->state->is_overdue) {
            $this->controls->addAttributes(['class' => 'overdue']);
        }

        $this->addControl((new HostList([$this->host]))->setViewMode('minimal'));
        $this->addControl(new QuickActions($this->host));

        $this->addContent(new HostDetail($this->host, $serviceSummary->first()));

        $this->setAutorefreshInterval(10);
    }

    public function commentsAction()
    {
        $this->setTitle($this->translate('Comments'));

        $this->addControl((new HostList([$this->host]))->setViewMode('minimal'));

        $comments = $this->host->comment;

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($comments);

        yield $this->export($comments);

        $this->addControl($paginationControl);
        $this->addControl($limitControl);

        $this->addContent(new CommentList($comments));

        $this->setAutorefreshInterval(10);
    }

    public function downtimesAction()
    {
        $this->setTitle($this->translate('Downtimes'));

        $this->addControl((new HostList([$this->host]))->setViewMode('minimal'));

        $downtimes = $this->host->downtime;

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($downtimes);

        yield $this->export($downtimes);

        $this->addControl($paginationControl);
        $this->addControl($limitControl);

        $this->addContent(new DowntimeList($downtimes));

        $this->setAutorefreshInterval(10);
    }

    public function historyAction()
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
            'notification',
            'acknowledgement',
            'state'
        ]);

        $history
            ->getSelectBase()
            ->where([
                'history_host.id = ?' => $this->host->id,
                'history.object_type = ?' => 'host'
            ]);

        $url = HostLinks::history($this->host);
        if (! $this->params->has('page') || ($page = (int) $this->params->shift('page')) < 1) {
            $page = 1;
        }

        $limitControl = $this->createLimitControl();

        $history->peekAhead();
        $history->limit($limitControl->getLimit());
        if ($page > 1) {
            if ($compact) {
                $history->offset(($page - 1) * $limitControl->getLimit());
            } else {
                $history->limit($page * $limitControl->getLimit());
            }
        }

        yield $this->export($history);

        $results = $history->execute();

        $showMore = (new ShowMore(
            $results,
            $url->setParam('page', $page + 1)
                ->setAnchor('page-' . ($page + 1))
        ))
            ->setLabel('Load More')
            ->setAttribute('data-no-icinga-ajax', true);

        $this->addControl((new HostList([$this->host]))->setViewMode('minimal'));
        $this->addControl($limitControl);

        $historyList = (new HistoryList($results))
            ->setPageSize($limitControl->getLimit());
        if ($compact) {
            $historyList->setPageNumber($page);
        }

        // TODO: Dirty, really dirty, find a better solution (And I don't just mean `getContent()` !)
        $historyList->add($showMore->setTag('li')->addAttributes(['class' => 'list-item']));
        if ($compact && $page > 1) {
            $this->document->add($historyList->getContent());
        } else {
            $this->addContent($historyList);
        }
    }

    public function servicesAction()
    {
        if ($this->host->state->is_overdue) {
            $this->controls->addAttributes(['class' => 'overdue']);
        }

        $db = $this->getDb();

        $services = Service::on($db)->with([
            'state',
            'host',
            'host.state'
        ]);

        $services
            ->getSelectBase()
            ->where(['service_host.id = ?' => $this->host->id]);

        $this->applyMonitoringRestriction($services);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($services);
        $viewModeSwitcher = $this->createViewModeSwitcher();

        yield $this->export($services);

        $serviceList = (new ServiceList($services))
            ->setViewMode($viewModeSwitcher->getViewMode());

        $this->addControl((new HostList([$this->host]))->setViewMode('minimal'));
        $this->addControl($paginationControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($limitControl);

        $this->addContent($serviceList);

        $this->setAutorefreshInterval(10);
    }

    protected function createTabs()
    {
        return $this
            ->getTabs()
            ->add('index', [
                'label'  => $this->translate('Host'),
                'url'    => Links::host($this->host)
            ])
            ->add('services', [
                'label'  => $this->translate('Services'),
                'url'    => HostLinks::services($this->host)
            ])
            ->add('history', [
                'label'  => $this->translate('History'),
                'url'    => HostLinks::history($this->host)
            ]);
    }

    protected function setTitleTab($name)
    {
        $tab = $this->createTabs()->get($name);

        if ($tab !== null) {
            $tab->setActive();

            $this->view->title = $tab->getLabel();
        }
    }

    protected function fetchCommandTargets()
    {
        return [$this->host];
    }

    protected function getCommandTargetsUrl()
    {
        return Links::host($this->host);
    }
}
