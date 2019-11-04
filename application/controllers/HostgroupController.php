<?php

namespace Icinga\Module\Eagle\Controllers;

use Icinga\Data\Filter\FilterExpression;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Eagle\Model\Host;
use Icinga\Module\Eagle\Model\Hostgroupsummary;
use Icinga\Module\Eagle\Web\Controller;
use Icinga\Module\Eagle\Widget\HostList;
use Icinga\Module\Eagle\Widget\ItemList\HostgroupList;
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
    }
}
