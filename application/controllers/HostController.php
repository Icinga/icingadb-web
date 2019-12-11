<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Common\CommandActions;
use Icinga\Module\Icingadb\Common\HostLinks;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\ObjectDetail;
use Icinga\Module\Icingadb\Widget\Detail\QuickActions;
use Icinga\Module\Icingadb\Widget\DowntimeList;
use Icinga\Module\Icingadb\Widget\HostList;
use Icinga\Module\Icingadb\Widget\ItemList\CommentList;
use Icinga\Module\Icingadb\Widget\ItemList\HistoryList;
use Icinga\Module\Icingadb\Widget\ServiceList;

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

    protected function getCommandTargetsUrl()
    {
        return Links::host($this->host);
    }

    protected function fetchCommandTargets()
    {
        return [$this->host];
    }

    public function indexAction()
    {
        if ($this->host->state->is_overdue) {
            $this->controls->addAttributes(['class' => 'overdue']);
        }

        $this->addControl((new HostList([$this->host]))->setViewMode('minimal'));
        $this->addControl(new QuickActions($this->host));

        $this->addContent(new ObjectDetail($this->host));

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
        if ($this->host->state->is_overdue) {
            $this->controls->addAttributes(['class' => 'overdue']);
        }

        $this->addControl((new HostList([$this->host]))->setViewMode('minimal'));

        $db = $this->getDb();

        $history = History::on($db)->with([
            'host',
            'host.state',
            'comment',
            'downtime',
            'notification',
            'state'
        ]);

        $history
            ->getSelectBase()
            ->where([
                'history_host.id = ?' => $this->host->id,
                'history.object_type = ?' => 'host'
            ]);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($history);

        yield $this->export($history);

        $this->addControl($paginationControl);
        $this->addControl($limitControl);

        $this->addContent(new HistoryList($history));
    }

    public function servicesAction()
    {
        if ($this->host->state->is_overdue) {
            $this->controls->addAttributes(['class' => 'overdue']);
        }

        $this->addControl((new HostList([$this->host]))->setViewMode('minimal'));

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
}
