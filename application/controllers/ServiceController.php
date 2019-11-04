<?php

namespace Icinga\Module\Eagle\Controllers;

use Exception;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Eagle\Common\CommandActions;
use Icinga\Module\Eagle\Common\Links;
use Icinga\Module\Eagle\Common\ServiceLinks;
use Icinga\Module\Eagle\Model\History;
use Icinga\Module\Eagle\Model\Service;
use Icinga\Module\Eagle\Web\Controller;
use Icinga\Module\Eagle\Widget\Detail\ObjectDetail;
use Icinga\Module\Eagle\Widget\Detail\QuickActions;
use Icinga\Module\Eagle\Widget\DowntimeList;
use Icinga\Module\Eagle\Widget\HostList;
use Icinga\Module\Eagle\Widget\ItemList\CommentList;
use Icinga\Module\Eagle\Widget\ItemList\HistoryList;
use Icinga\Module\Eagle\Widget\ServiceList;
use ipl\Sql\Sql;

class ServiceController extends Controller
{
    use CommandActions;

    /** @var Service The service object */
    protected $service;

    public function init()
    {
        $name = $this->params->shiftRequired('name');
        $hostName = $this->params->shiftRequired('host.name');

        $query = Service::on($this->getDb())->with([
            'state',
            'host',
            'host.state'
        ]);
        $query->getSelectBase()
            ->where(['service.name = ?' => $name])
            ->where(['service_host.name = ?' => $hostName]);

        /** @var Service $service */
        $service = $query->first();
        if ($service === null) {
            throw new NotFoundError($this->translate('Service not found'));
        }

        $this->service = $service;

        $this->setTitleTab($this->getRequest()->getActionName());
    }

    public function getCommandTargetsUrl()
    {
        return Links::service($this->service, $this->service->host);
    }

    public function fetchCommandTargets()
    {
        return [$this->service];
    }

    public function indexAction()
    {
        $this->addControl((new ServiceList([$this->service]))->setViewMode('compact'));
        $this->addControl(new QuickActions($this->service));

        $this->addContent(new ObjectDetail($this->service));
    }

    public function commentsAction()
    {
        $this->setTitle($this->translate('Comments'));

        $this->addControl((new ServiceList([$this->service]))->setViewMode('compact'));

        $comments = $this->service->comment;

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($comments);

        yield $this->export($comments);

        $this->addControl($paginationControl);
        $this->addControl($limitControl);

        $this->addContent(new CommentList($comments));
    }

    public function downtimesAction()
    {
        $this->setTitle($this->translate('Downtimes'));

        $this->addControl((new ServiceList([$this->service]))->setViewMode('compact'));

        $downtimes = $this->service->downtime;

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($downtimes);

        yield $this->export($downtimes);

        $this->addControl($paginationControl);
        $this->addControl($limitControl);

        $this->addContent(new DowntimeList($downtimes));
    }

    public function historyAction()
    {
        $this->addControl((new ServiceList([$this->service]))->setViewMode('compact'));

        $db = $this->getDb();

        $history = History::on($db)->with([
            'host',
            'host.service',
            'host.state',
            'service',
            'service.state',
            'service.host',
            'service.host.state',
            'comment',
            'downtime',
            'notification',
            'state'
        ]);

        $history
            ->getSelectBase()
            ->where([
                'history_host_service.id = ?' => $this->service->id,
                'history_service.id = ?' => $this->service->id
            ], Sql::ANY);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($history);

        yield $this->export($history);

        $this->addControl($paginationControl);
        $this->addControl($limitControl);

        $this->addContent(new HistoryList($history));
    }

    protected function createTabs()
    {
        return $this
            ->getTabs()
            ->add('index', [
                'label'  => $this->translate('Service'),
                'url'    => Links::service($this->service, $this->service->host)
            ])
            ->add('history', [
                'label'  => $this->translate('History'),
                'url'    => ServiceLinks::history($this->service, $this->service->host)
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
