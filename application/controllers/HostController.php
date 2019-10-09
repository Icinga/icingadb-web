<?php

namespace Icinga\Module\Eagle\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Eagle\Model\Host;
use Icinga\Module\Eagle\Redis\VolatileState;
use Icinga\Module\Eagle\Web\Controller;
use Icinga\Module\Eagle\Widget\HostListItem;

class HostController extends Controller
{
    public function indexAction()
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

        $volatileState = new VolatileState($this->getRedis());
        $volatileState->add($host);
        $volatileState->fetch();

        $this->addContent((new HostListItem($host))->setTag('div'));
    }
}
