<?php

namespace Icinga\Module\Eagle\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Eagle\Common\CommandActions;
use Icinga\Module\Eagle\Model\Host;
use Icinga\Module\Eagle\Web\Controller;
use Icinga\Module\Eagle\Widget\HostListItem;

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
    }

    protected function fetchCommandTargets()
    {
        return [$this->host];
    }

    public function indexAction()
    {
        $this->addContent((new HostListItem($this->host))->setTag('div'));
    }
}
