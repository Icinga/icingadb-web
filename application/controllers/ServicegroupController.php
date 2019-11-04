<?php

namespace Icinga\Module\Eagle\Controllers;

use Icinga\Data\Filter\FilterExpression;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Eagle\Model\Service;
use Icinga\Module\Eagle\Model\Servicegroupsummary;
use Icinga\Module\Eagle\Web\Controller;
use Icinga\Module\Eagle\Widget\ServiceList;
use Icinga\Module\Eagle\Widget\ItemList\ServicegroupList;
use ipl\Orm\Compat\FilterProcessor;

class ServicegroupController extends Controller
{
    /** @var Servicegroupsummary The service group object */
    protected $servicegroup;

    public function init()
    {
        $this->setTitle($this->translate('Service Group'));

        $name = $this->params->shiftRequired('name');

        $query = Servicegroupsummary::on($this->getDb());

        FilterProcessor::apply(
            new FilterExpression('servicegroup.name', '=', $name),
            $query
        );

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
    }
}
