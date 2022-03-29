<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Model\ServicegroupSummary;
use Icinga\Module\Icingadb\Redis\VolatileStateResults;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\ServiceList;
use Icinga\Module\Icingadb\Widget\ItemList\ServicegroupList;
use ipl\Html\Html;
use ipl\Stdlib\Filter;

class ServicegroupController extends Controller
{
    /** @var ServicegroupSummary The service group object */
    protected $servicegroup;

    public function init()
    {
        $this->assertRouteAccess('servicegroups');

        $this->addTitleTab(t('Service Group'));

        $name = $this->params->getRequired('name');

        $query = ServicegroupSummary::on($this->getDb());

        foreach ($query->getUnions() as $unionPart) {
            $unionPart->filter(Filter::equal('servicegroup.name', $name));
        }

        $this->applyRestrictions($query);

        $servicegroup = $query->first();
        if ($servicegroup === null) {
            throw new NotFoundError(t('Service group not found'));
        }

        $this->servicegroup = $servicegroup;
        $this->setTitle($servicegroup->display_name);
    }

    public function indexAction()
    {
        $db = $this->getDb();

        $services = Service::on($db)->with([
            'state',
            'state.last_comment',
            'icon_image',
            'host',
            'host.state'
        ])->utilize('servicegroup');
        $services
            ->setResultSetClass(VolatileStateResults::class)
            ->filter(Filter::equal('servicegroup.id', $this->servicegroup->id));

        $this->applyRestrictions($services);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($services);
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);

        $serviceList = (new ServiceList($services))
            ->setViewMode($viewModeSwitcher->getViewMode());

        yield $this->export($services);

        // ICINGAWEB_EXPORT_FORMAT is not set yet and $this->format is inaccessible, yeah...
        if ($this->getRequest()->getParam('format') === 'pdf') {
            $this->addContent((new ServicegroupList([$this->servicegroup]))
                ->setViewMode('minimal')
                ->setDetailActionsDisabled()
                ->setNoSubjectLink());
            $this->addContent(Html::tag('h2', null, t('Services')));
        } else {
            $this->addControl((new ServicegroupList([$this->servicegroup]))
                ->setViewMode('minimal')
                ->setDetailActionsDisabled()
                ->setNoSubjectLink());
        }

        $this->addControl($paginationControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($limitControl);

        $this->addContent($serviceList);

        $this->setAutorefreshInterval(10);
    }
}
