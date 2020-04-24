<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

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
        $this->setTitle(t('Service Group'));

        $name = $this->params->shiftRequired('name');

        $query = ServicegroupSummary::on($this->getDb());

        FilterProcessor::apply(
            new FilterExpression('servicegroup.name', '=', $name),
            $query
        );

        $this->applyMonitoringRestriction($query);

        $servicegroup = $query->first();
        if ($servicegroup === null) {
            throw new NotFoundError(t('Service group not found'));
        }

        $this->servicegroup = $servicegroup;
    }

    public function indexAction()
    {
        $db = $this->getDb();

        $services = Service::on($db)->with([
            'state',
            'host',
            'host.state'
        ])->utilize('servicegroup');

        $services->getSelectBase()->where(['service_servicegroup.id = ?' => $this->servicegroup->id]);
        $this->applyMonitoringRestriction($services);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($services);
        $viewModeSwitcher = $this->createViewModeSwitcher();

        $serviceList = (new ServiceList($services))
            ->setViewMode($viewModeSwitcher->getViewMode());

        yield $this->export($services);

        $this->addControl((new ServicegroupList([$this->servicegroup]))->setViewMode('minimal'));
        $this->addControl($paginationControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($limitControl);

        $this->addContent($serviceList);

        $this->setAutorefreshInterval(10);
    }
}
