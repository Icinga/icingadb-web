<?php

namespace Icinga\Module\Eagle\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Eagle\Model\Host;
use Icinga\Module\Eagle\Web\Controller;
use Icinga\Module\Eagle\Widget\HostList;

class HostsController extends Controller
{
    public function indexAction()
    {
        $this->setTitle($this->translate('Hosts'));

        $db = $this->getDb();

        $hosts = Host::on($db)->with('state');
        // @TODO(el): Looks a bit odd since createLimitControl() also shifts the limit
        $hosts->limit($this->params->shift('limit'));

        $viewModeSwitcher = $this->createViewModeSwitcher();
        $limitControl = $this->createLimitControl();
        $limitControl->handleRequest(ServerRequest::fromGlobals());

        $hostList = (new HostList($hosts))
            ->setRedis($this->getRedis())
            ->setViewMode($viewModeSwitcher->getViewMode());

        $this->addControl($this->createPaginationControl($hosts));
        $this->addControl($viewModeSwitcher);
        $this->addControl($limitControl);

        $this->addContent($hostList);
    }
}
