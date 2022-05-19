<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use ArrayObject;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\EventDetail;
use Icinga\Module\Icingadb\Widget\ItemList\HistoryList;
use ipl\Orm\ResultSet;
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
            ->with('host')
            ->with('host.state')
            ->with('service')
            ->with('service.state')
            ->with('comment')
            ->with('downtime')
            ->with('downtime.parent')
            ->with('downtime.parent.host')
            ->with('downtime.parent.host.state')
            ->with('downtime.parent.service')
            ->with('downtime.parent.service.state')
            ->with('downtime.triggered_by')
            ->with('downtime.triggered_by.host')
            ->with('downtime.triggered_by.host.state')
            ->with('downtime.triggered_by.service')
            ->with('downtime.triggered_by.service.state')
            ->with('flapping')
            ->with('notification')
            ->with('acknowledgement')
            ->with('state')
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
        $this->addControl((new HistoryList(new ResultSet(new ArrayObject([$this->event]))))
            ->setViewMode('minimal')
            ->setPageSize(1)
            ->setCaptionDisabled()
            ->setNoSubjectLink()
            ->setDetailActionsDisabled());
        $this->addContent(new EventDetail($this->event));
    }
}
