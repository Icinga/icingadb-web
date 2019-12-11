<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Data\Filter\FilterExpression;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Hostgroupsummary;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\HostList;
use Icinga\Module\Icingadb\Widget\ItemList\HostgroupList;
use ipl\Orm\Compat\FilterProcessor;

class HostgroupController extends Controller
{
    /** @var Hostgroupsummary The host group object */
    protected $hostgroup;

    public function init()
    {
        $this->setTitle($this->translate('Host Group'));

        $name = $this->params->shiftRequired('name');

        $query = Hostgroupsummary::on($this->getDb());

        FilterProcessor::apply(
            new FilterExpression('hostgroup.name', '=', $name),
            $query
        );

        $this->applyMonitoringRestriction($query);

        $hostgroup = $query->first();
        if ($hostgroup === null) {
            throw new NotFoundError($this->translate('Host group not found'));
        }

        $this->hostgroup = $hostgroup;
    }

    public function indexAction()
    {
        $this->addControl((new HostgroupList([$this->hostgroup])));

        $db = $this->getDb();

        $hosts = Host::on($db)->with('state');

        FilterProcessor::apply(
            new FilterExpression('hostgroup.id', '=', $this->hostgroup->id),
            $hosts
        );

        $this->applyMonitoringRestriction($hosts);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($hosts);
        $viewModeSwitcher = $this->createViewModeSwitcher();

        $hostList = (new HostList($hosts))
            ->setViewMode($viewModeSwitcher->getViewMode());

        yield $this->export($hosts);

        $this->addControl($paginationControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($limitControl);

        $this->addContent($hostList);

        $this->setAutorefreshInterval(10);
    }
}
