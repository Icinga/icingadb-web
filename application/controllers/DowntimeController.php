<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Model\Downtime;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\DowntimeDetail;
use Icinga\Module\Icingadb\Widget\Detail\ObjectHeader;
use ipl\Stdlib\Filter;

class DowntimeController extends Controller
{
    /** @var Downtime */
    protected $downtime;

    public function init()
    {
        $this->addTitleTab(t('Downtime'));

        $name = $this->params->getRequired('name');

        $query = Downtime::on($this->getDb())->with([
            'host',
            'host.state',
            'service',
            'service.state',
            'service.host',
            'service.host.state',
            'parent',
            'parent.host',
            'parent.host.state',
            'parent.service',
            'parent.service.state',
            'triggered_by',
            'triggered_by.host',
            'triggered_by.host.state',
            'triggered_by.service',
            'triggered_by.service.state'
        ]);
        $query->filter(Filter::equal('downtime.name', $name));

        $this->applyRestrictions($query);

        $downtime = $query->first();
        if ($downtime === null) {
            throw new NotFoundError(t('Downtime not found'));
        }

        $this->downtime = $downtime;
    }

    public function indexAction()
    {
        $detail = new DowntimeDetail($this->downtime);

        $this->addControl(new ObjectHeader($this->downtime));

        $this->addContent($detail);

        $this->setAutorefreshInterval(10);
    }
}
