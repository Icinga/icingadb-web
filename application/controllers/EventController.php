<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\EventDetail;
use Icinga\Module\Icingadb\Widget\Detail\ObjectHeader;
use ipl\Stdlib\Filter;

class EventController extends Controller
{
    /** @var History */
    protected $event;

    public function init()
    {
        $this->addTitleTab(t('Event'));

        $id = $this->params->getRequired('id');

        $query = History::on($this->getDb())
            ->with([
               'host',
               'host.state',
               'service',
               'service.state',
               'comment',
               'downtime',
               'downtime.parent',
               'downtime.parent.host',
               'downtime.parent.host.state',
               'downtime.parent.service',
               'downtime.parent.service.state',
               'downtime.triggered_by',
               'downtime.triggered_by.host',
               'downtime.triggered_by.host.state',
               'downtime.triggered_by.service',
               'downtime.triggered_by.service.state',
               'flapping',
               'notification',
               'acknowledgement',
               'state'
            ])
            ->filter(Filter::equal('id', hex2bin($id)));

        $this->applyRestrictions($query);

        $event = $query->first();
        if ($event === null) {
            $this->httpNotFound(t('Event not found'));
        }

        $this->event = $event;
    }

    public function indexAction()
    {
        $this->addControl(new ObjectHeader($this->event));
        $this->addContent(new EventDetail($this->event));
    }
}
