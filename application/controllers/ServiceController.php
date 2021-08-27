<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Command\Object\GetObjectCommand;
use Icinga\Module\Icingadb\Command\Transport\CommandTransport;
use Icinga\Module\Icingadb\Common\CommandActions;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\ServiceLinks;
use Icinga\Module\Icingadb\Hook\TabHook\HookActions;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\QuickActions;
use Icinga\Module\Icingadb\Widget\Detail\ServiceDetail;
use Icinga\Module\Icingadb\Widget\Detail\ServiceInspectionDetail;
use Icinga\Module\Icingadb\Widget\ItemList\HistoryList;
use Icinga\Module\Icingadb\Widget\ItemList\ServiceList;
use ipl\Web\Url;

class ServiceController extends Controller
{
    use CommandActions;
    use HookActions;

    /** @var Service The service object */
    protected $service;

    public function init()
    {
        $name = $this->params->getRequired('name');
        $hostName = $this->params->getRequired('host.name');

        $query = Service::on($this->getDb())->with([
            'state',
            'icon_image',
            'host',
            'host.state'
        ]);
        $query->getSelectBase()
            ->where(['service.name = ?' => $name])
            ->where(['service_host.name = ?' => $hostName]);

        $this->applyRestrictions($query);

        /** @var Service $service */
        $service = $query->first();
        if ($service === null) {
            throw new NotFoundError(t('Service not found'));
        }

        $this->service = $service;
        $this->loadTabsForObject($service);

        $this->setTitleTab($this->getRequest()->getActionName());
    }

    public function indexAction()
    {
        if ($this->service->state->is_overdue) {
            $this->controls->addAttributes(['class' => 'overdue']);
        }

        $this->addControl((new ServiceList([$this->service]))
            ->setViewMode('minimal')
            ->setDetailActionsDisabled()
            ->setNoSubjectLink());
        $this->addControl(new QuickActions($this->service));

        $this->addContent(new ServiceDetail($this->service));

        $this->setAutorefreshInterval(10);
    }

    public function sourceAction()
    {
        $this->assertPermission('icingadb/object/show-source');
        $apiResult = (new CommandTransport())->send((new GetObjectCommand())->setObject($this->service));

        if ($this->service->state->is_overdue) {
            $this->controls->addAttributes(['class' => 'overdue']);
        }

        $this->addControl((new ServiceList([$this->service]))
            ->setDetailActionsDisabled()
            ->setNoSubjectLink());
        $this->addContent(new ServiceInspectionDetail(
            $this->service,
            reset($apiResult)
        ));
    }

    public function historyAction()
    {
        $compact = $this->view->compact; // TODO: Find a less-legacy way..

        if ($this->service->state->is_overdue) {
            $this->controls->addAttributes(['class' => 'overdue']);
        }

        $db = $this->getDb();

        $history = History::on($db)->with([
            'host',
            'host.service',
            'host.state',
            'service',
            'service.state',
            'comment',
            'downtime',
            'flapping',
            'notification',
            'acknowledgement',
            'state'
        ]);

        $history
            ->getSelectBase()
            ->where([
                'history_host_service.id = ?' => $this->service->id,
                'history_service.id = ?' => $this->service->id
            ]);

        $before = $this->params->shift('before', time());
        $url = Url::fromRequest()->setParams(clone $this->params);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($history);
        $sortControl = $this->createSortControl(
            $history,
            [
                'history.event_time desc' => t('Event Time')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);

        $history->peekAhead();

        $page = $paginationControl->getCurrentPageNumber();

        if ($page > 1 && ! $compact) {
            $history->limit($page * $limitControl->getLimit());
        }

        yield $this->export($history);

        $this->addControl((new ServiceList([$this->service]))->setViewMode('minimal'));
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);

        $historyList = (new HistoryList($history->execute()))
            ->setViewMode($viewModeSwitcher->getViewMode())
            ->setPageSize($limitControl->getLimit())
            ->setLoadMoreUrl($url->setParam('before', $before));

        if ($compact) {
            $historyList->setPageNumber($page);
        }

        if ($compact && $page > 1) {
            $this->document->addFrom($historyList);
        } else {
            $this->addContent($historyList);
        }
    }

    protected function createTabs()
    {
        $tabs = $this->getTabs()
            ->add('index', [
                'label'  => t('Service'),
                'url'    => Links::service($this->service, $this->service->host)
            ])
            ->add('history', [
                'label'  => t('History'),
                'url'    => ServiceLinks::history($this->service, $this->service->host)
            ]);

        if ($this->hasPermission('icingadb/object/show-source')) {
            $tabs->add('source', [
                'label' => t('Source'),
                'url'   => Links::serviceSource($this->service, $this->service->host)
            ]);
        }

        foreach ($this->loadAdditionalTabs() as $name => $tab) {
            $tabs->add($name, $tab + ['urlParams' => [
                'name'      => $this->service->name,
                'host.name' => $this->service->host->name
            ]]);
        }

        return $tabs;
    }

    protected function setTitleTab($name)
    {
        $tab = $this->createTabs()->get($name);

        if ($tab !== null) {
            $tab->setActive();

            $this->view->title = $tab->getLabel();
        }
    }

    public function fetchCommandTargets()
    {
        return [$this->service];
    }

    public function getCommandTargetsUrl()
    {
        return Links::service($this->service, $this->service->host);
    }

    protected function getDefaultTabControls()
    {
        return [(new ServiceList([$this->service]))->setDetailActionsDisabled()->setNoSubjectLink()];
    }
}
