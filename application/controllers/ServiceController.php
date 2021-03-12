<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Common\CommandActions;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\ServiceLinks;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\QuickActions;
use Icinga\Module\Icingadb\Widget\Detail\ServiceDetail;
use Icinga\Module\Icingadb\Widget\DowntimeList;
use Icinga\Module\Icingadb\Widget\ItemList\CommentList;
use Icinga\Module\Icingadb\Widget\ItemList\HistoryList;
use Icinga\Module\Icingadb\Widget\ServiceList;
use Icinga\Module\Icingadb\Widget\ShowMore;
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

        $this->applyRestrictions($query);

        /** @var Service $service */
        $service = $query->first();
        if ($service === null) {
            throw new NotFoundError(t('Service not found'));
        }

        $this->service = $service;

        $this->setTitleTab($this->getRequest()->getActionName());
    }

    public function indexAction()
    {
        if ($this->service->state->is_overdue) {
            $this->controls->addAttributes(['class' => 'overdue']);
        }
        $this->addControl((new ServiceList([$this->service]))->setViewMode('minimal'));
        $this->addControl(new QuickActions($this->service));

        $this->addContent(new ServiceDetail($this->service));

        $this->setAutorefreshInterval(10);
    }

    public function commentsAction()
    {
        $this->setTitle(t('Comments'));

        $comments = $this->service->comment;

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($comments);

        yield $this->export($comments);

        $this->addControl((new ServiceList([$this->service]))->setViewMode('minimal'));
        $this->addControl($paginationControl);
        $this->addControl($limitControl);

        $this->addContent(new CommentList($comments));

        $this->setAutorefreshInterval(10);
    }

    public function downtimesAction()
    {
        $this->setTitle(t('Downtimes'));

        $downtimes = $this->service->downtime;

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($downtimes);

        yield $this->export($downtimes);

        $this->addControl((new ServiceList([$this->service]))->setViewMode('minimal'));
        $this->addControl($paginationControl);
        $this->addControl($limitControl);

        $this->addContent(new DowntimeList($downtimes));

        $this->setAutorefreshInterval(10);
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

        $url = ServiceLinks::history($this->service, $this->service->host);
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
            ->setLabel(t('Load More'))
            ->setAttribute('data-no-icinga-ajax', true);

        $this->addControl((new ServiceList([$this->service]))->setViewMode('minimal'));
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

    protected function createTabs()
    {
        return $this
            ->getTabs()
            ->add('index', [
                'label'  => t('Service'),
                'url'    => Links::service($this->service, $this->service->host)
            ])
            ->add('history', [
                'label'  => t('History'),
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

    public function fetchCommandTargets()
    {
        return [$this->service];
    }

    public function getCommandTargetsUrl()
    {
        return Links::service($this->service, $this->service->host);
    }
}
