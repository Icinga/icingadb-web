<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Common\CommandActions;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Downtime;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\DowntimeDetail;

class DowntimeController extends Controller
{
    use CommandActions;

    /** @var Downtime */
    protected $downtime;

    public function init()
    {
        $this->setTitle($this->translate('Downtime'));

        $name = $this->params->shiftRequired('name');

        $query = Downtime::on($this->getDb());
        $query->getSelectBase()
            ->where(['downtime.name = ?' => $name]);

        $this->applyMonitoringRestriction($query);

        $downtime = $query->first();
        if ($downtime === null) {
            throw new NotFoundError($this->translate('Downtime not found'));
        }

        $this->downtime = $downtime;
    }

    public function indexAction()
    {
        $downtimeDetail = new DowntimeDetail($this->downtime);

        $this->addControl($downtimeDetail->getControl());

        $this->addContent($downtimeDetail);
    }

    protected function fetchCommandTargets()
    {
        return [$this->downtime];
    }

    protected function getCommandTargetsUrl()
    {
        return Links::downtime($this->downtime);
    }
}
