<?php

namespace Icinga\Module\Eagle\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Eagle\Common\CommandActions;
use Icinga\Module\Eagle\Model\Host;
use Icinga\Module\Eagle\Web\Controller;
use Icinga\Module\Eagle\Widget\Detail\ObjectDetail;
use Icinga\Module\Eagle\Widget\Detail\QuickActions;
use Icinga\Module\Eagle\Widget\HostList;
use ipl\Web\Url;

class HostController extends Controller
{
    use CommandActions;

    /** @var Host The host object */
    protected $host;

    public function init()
    {
        $this->setTitle($this->translate('Host'));

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

    protected function getCommandTargetsUrl()
    {
        return Url::fromPath('eagle/host', ['name' => $this->host->name]);
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
}
