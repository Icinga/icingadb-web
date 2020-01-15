<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Common\CommandActions;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Downtime;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\DowntimeDetail;
use Icinga\Module\Icingadb\Widget\DowntimeList;

class DowntimeController extends Controller
{
    use CommandActions;

    /** @var Downtime */
    protected $downtime;

    public function init()
    {
        $this->setTitle($this->translate('Downtime'));

        $name = $this->params->shiftRequired('name');

        $query = Downtime::on($this->getDb())->with([
            'host',
            'host.state',
            'service',
            'service.state',
            'service.host',
            'service.host.state'
        ]);
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
        $detail = new DowntimeDetail($this->downtime);

        $this->addControl((new DowntimeList([$this->downtime]))->setViewMode('minimal')->setCaptionDisabled());

        $this->addContent($detail);

        $this->setAutorefreshInterval(10);
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
