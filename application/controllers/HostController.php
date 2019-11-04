<?php

namespace Icinga\Module\Eagle\Controllers;

use Exception;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Eagle\Common\CommandActions;
use Icinga\Module\Eagle\Common\HostLinks;
use Icinga\Module\Eagle\Common\Links;
use Icinga\Module\Eagle\Model\History;
use Icinga\Module\Eagle\Model\Host;
use Icinga\Module\Eagle\Model\Service;
use Icinga\Module\Eagle\Web\Controller;
use Icinga\Module\Eagle\Widget\Detail\ObjectDetail;
use Icinga\Module\Eagle\Widget\Detail\QuickActions;
use Icinga\Module\Eagle\Widget\HostList;
use Icinga\Module\Eagle\Widget\ItemList\HistoryList;
use Icinga\Module\Eagle\Widget\ServiceList;

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
            ->where(['name = ?' => $name]);

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
        $this->addControl((new HostList([$this->host]))->setViewMode('compact'));
        $this->addControl(new QuickActions($this->host));

        $this->addContent(new ObjectDetail($this->host));
    }

    public function historyAction()
    {
        $this->addControl((new HostList([$this->host]))->setViewMode('compact'));

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
        $this->addControl((new HostList([$this->host]))->setViewMode('compact'));

        $db = $this->getDb();

        $services = Service::on($db)->with([
            'state',
            'host',
            'host.state'
        ]);

        $services
            ->getSelectBase()
            ->where(['service_host.id = ?' => $this->host->id]);

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
