<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Command\Object\GetObjectCommand;
use Icinga\Module\Icingadb\Command\Transport\CommandTransport;
use Icinga\Module\Icingadb\Common\CommandActions;
use Icinga\Module\Icingadb\Common\HostLinks;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Hook\TabHook\HookActions;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\HostDetail;
use Icinga\Module\Icingadb\Widget\Detail\HostInspectionDetail;
use Icinga\Module\Icingadb\Widget\Detail\HostMetaInfo;
use Icinga\Module\Icingadb\Widget\Detail\QuickActions;
use Icinga\Module\Icingadb\Widget\ItemList\HostList;
use Icinga\Module\Icingadb\Widget\ItemList\HistoryList;
use Icinga\Module\Icingadb\Widget\ItemList\ServiceList;

class HostController extends Controller
{
    use CommandActions;
    use HookActions;

    /** @var Host The host object */
    protected $host;

    public function init()
    {
        $name = $this->params->shiftRequired('name');

        $query = Host::on($this->getDb())->with(['state', 'icon_image']);
        $query->getSelectBase()
            ->where(['host.name = ?' => $name]);

        $this->applyRestrictions($query);

        /** @var Host $host */
        $host = $query->first();
        if ($host === null) {
            throw new NotFoundError(t('Host not found'));
        }

        $this->host = $host;
        $this->loadTabsForObject($host);

        $this->setTitleTab($this->getRequest()->getActionName());
    }

    public function indexAction()
    {
        $serviceSummary = ServicestateSummary::on($this->getDb())->with('state');
        $serviceSummary->getSelectBase()
            ->where(['host_id = ?' => $this->host->id]);

        $this->applyRestrictions($serviceSummary);

        if ($this->host->state->is_overdue) {
            $this->controls->addAttributes(['class' => 'overdue']);
        }

        $this->addControl((new HostList([$this->host]))
            ->setViewMode('minimal')
            ->setDetailActionsDisabled()
            ->setNoSubjectLink());
        $this->addControl(new HostMetaInfo($this->host));
        $this->addControl(new QuickActions($this->host));

        $this->addContent(new HostDetail($this->host, $serviceSummary->first()));

        $this->setAutorefreshInterval(10);
    }

    public function sourceAction()
    {
        $this->assertPermission('icingadb/object/show-source');
        $apiResult = (new CommandTransport())->send((new GetObjectCommand())->setObject($this->host));

        if ($this->host->state->is_overdue) {
            $this->controls->addAttributes(['class' => 'overdue']);
        }

        $this->addControl((new HostList([$this->host]))
            ->setDetailActionsDisabled()
            ->setNoSubjectLink());
        $this->addContent(new HostInspectionDetail(
            $this->host,
            reset($apiResult)
        ));
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
            'flapping',
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
        $url->setParam('limit', $limitControl->getLimit());
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

        $this->addControl((new HostList([$this->host]))->setViewMode('minimal'));
        $this->addControl($limitControl);

        $historyList = (new HistoryList($history->execute()))
            ->setPageSize($limitControl->getLimit())
            ->setLoadMoreUrl($url);
        if ($compact) {
            $historyList->setPageNumber($page);
        }

        if ($compact && $page > 1) {
            $this->document->addFrom($historyList);
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
            'state.last_comment',
            'icon_image',
            'host',
            'host.state'
        ]);

        $services
            ->getSelectBase()
            ->where(['service_host.id = ?' => $this->host->id]);

        $this->applyRestrictions($services);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($services);
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);

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
        $tabs = $this->getTabs()
            ->add('index', [
                'label'  => t('Host'),
                'url'    => Links::host($this->host)
            ])
            ->add('services', [
                'label'  => t('Services'),
                'url'    => HostLinks::services($this->host)
            ])
            ->add('history', [
                'label'  => t('History'),
                'url'    => HostLinks::history($this->host)
            ]);

        if ($this->hasPermission('icingadb/object/show-source')) {
            $tabs->add('source', [
                'label' => t('Source'),
                'url' => Links::hostSource($this->host)
            ]);
        }

        foreach ($this->loadAdditionalTabs() as $name => $tab) {
            $tabs->add($name, $tab + ['urlParams' => ['name' => $this->host->name]]);
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

    protected function fetchCommandTargets()
    {
        return [$this->host];
    }

    protected function getCommandTargetsUrl()
    {
        return Links::host($this->host);
    }

    protected function getDefaultTabControls()
    {
        return [(new HostList([$this->host]))->setDetailActionsDisabled()->setNoSubjectLink()];
    }
}
