<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Common\CommandActions;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Downtime;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\DowntimeDetail;
use Icinga\Module\Icingadb\Widget\ItemList\DowntimeList;
use ipl\Stdlib\Filter;
use ipl\Web\Url;

class DowntimeController extends Controller
{
    use CommandActions;

    /** @var Downtime */
    protected $downtime;

    public function init()
    {
        $this->addTitleTab(t('Downtime'));

        $name = $this->params->getRequired('name');

        $query = Downtime::on($this->getDb())
            ->with('host')
            ->with('host.state')
            ->with('service')
            ->with('service.state')
            ->with('service.host')
            ->with('service.host.state')
            ->with('parent')
            ->with('parent.host')
            ->with('parent.host.state')
            ->with('parent.service')
            ->with('parent.service.state')
            ->with('triggered_by')
            ->with('triggered_by.host')
            ->with('triggered_by.host.state')
            ->with('triggered_by.service')
            ->with('triggered_by.service.state');
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

        $this->addControl((new DowntimeList([$this->downtime]))
            ->setViewMode('minimal')
            ->setDetailActionsDisabled()
            ->setCaptionDisabled()
            ->setNoSubjectLink());

        $this->addContent($detail);

        $this->setAutorefreshInterval(10);
    }

    protected function fetchCommandTargets(): array
    {
        return [$this->downtime];
    }

    protected function getCommandTargetsUrl(): Url
    {
        return Links::downtime($this->downtime);
    }
}
