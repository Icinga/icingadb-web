<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Data\Filter\FilterExpression;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Model\ServicegroupSummary;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ServiceList;
use Icinga\Module\Icingadb\Widget\ItemList\ServicegroupList;
use ipl\Orm\Compat\FilterProcessor;

class ServicegroupController extends Controller
{
    /** @var ServicegroupSummary The service group object */
    protected $servicegroup;

    public function init()
    {
        $this->setTitle($this->translate('Service Group'));

        $name = $this->params->shiftRequired('name');

        $query = ServicegroupSummary::on($this->getDb());

        FilterProcessor::apply(
            new FilterExpression('servicegroup.name', '=', $name),
            $query
        );

        $this->applyMonitoringRestriction($query);

        $servicegroup = $query->first();
        if ($servicegroup === null) {
            throw new NotFoundError($this->translate('Service group not found'));
        }

        $this->servicegroup = $servicegroup;
    }

    public function indexAction()
    {
        $this->addControl((new ServicegroupList([$this->servicegroup])));

        $db = $this->getDb();

        $services = Service::on($db)->with([
            'state',
            'host',
            'host.state'
        ]);

        FilterProcessor::apply(
            new FilterExpression('servicegroup.id', '=', $this->servicegroup->id),
            $services
        );

        $this->applyMonitoringRestriction($services);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($services);
        $viewModeSwitcher = $this->createViewModeSwitcher();

        $serviceList = (new ServiceList($services))
            ->setViewMode($viewModeSwitcher->getViewMode());

        yield $this->export($services);

        $this->addControl($paginationControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($limitControl);

        $this->addContent($serviceList);

        $this->setAutorefreshInterval(10);
    }
}
