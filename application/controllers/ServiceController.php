<?php

namespace Icinga\Module\Eagle\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Eagle\Model\Service;
use Icinga\Module\Eagle\Redis\VolatileState;
use Icinga\Module\Eagle\Web\Controller;
use Icinga\Module\Eagle\Widget\ServiceListItem;

class ServiceController extends Controller
{
    public function indexAction()
    {
        $name = $this->params->shiftRequired('name');
        $hostName = $this->params->shiftRequired('host_name');

        $query = Service::on($this->getDb())->with([
            'state',
            'host',
            'host.state'
        ]);
        $query->getSelectBase()
            ->where(['service.name = ?' => $name])
            ->where(['host.name = ?' => $hostName]);

        /** @var Service $service */
        $service = $query->first();
        if ($service === null) {
            throw new NotFoundError($this->translate('Service not found'));
        }

        $volatileState = new VolatileState($this->getRedis());
        $volatileState->add($service);
        $volatileState->fetch();

        $this->addContent((new ServiceListItem($service))->setTag('div'));
    }
}
