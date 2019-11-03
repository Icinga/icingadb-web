<?php

namespace Icinga\Module\Eagle\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Eagle\Common\CommandActions;
use Icinga\Module\Eagle\Common\Links;
use Icinga\Module\Eagle\Model\Service;
use Icinga\Module\Eagle\Web\Controller;
use Icinga\Module\Eagle\Widget\Detail\ObjectDetail;
use Icinga\Module\Eagle\Widget\Detail\QuickActions;
use Icinga\Module\Eagle\Widget\ServiceList;

class ServiceController extends Controller
{
    use CommandActions;

    /** @var Service The service object */
    protected $service;

    public function init()
    {
        $this->setTitle($this->translate('Service'));

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
}
