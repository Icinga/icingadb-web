<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Hostgroupsummary;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\HostList;
use Icinga\Module\Icingadb\Widget\ItemList\HostgroupList;
use ipl\Orm\Compat\FilterProcessor;
use ipl\Stdlib\Filter;

class HostgroupController extends Controller
{
    /** @var Hostgroupsummary The host group object */
    protected $hostgroup;

    public function init()
    {
        $this->setTitle(t('Host Group'));

        $name = $this->params->shiftRequired('name');

        $query = Hostgroupsummary::on($this->getDb());

        FilterProcessor::apply(
            Filter::equal('hostgroup.name', $name),
            $query
        );

        $this->applyMonitoringRestriction($query);

        $hostgroup = $query->first();
        if ($hostgroup === null) {
            throw new NotFoundError(t('Host group not found'));
        }

        $this->hostgroup = $hostgroup;
    }

    public function indexAction()
    {
        $db = $this->getDb();

        $hosts = Host::on($db)->with('state')->utilize('hostgroup');

        $hosts->getSelectBase()->where(['host_hostgroup.id = ?' => $this->hostgroup->id]);
        $this->applyMonitoringRestriction($hosts);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($hosts);
        $viewModeSwitcher = $this->createViewModeSwitcher();

        $hostList = (new HostList($hosts))
            ->setViewMode($viewModeSwitcher->getViewMode());

        yield $this->export($hosts);

        $this->addControl((new HostgroupList([$this->hostgroup]))->setViewMode('minimal'));
        $this->addControl($paginationControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($limitControl);

        $this->addContent($hostList);

        $this->setAutorefreshInterval(10);
    }
}
