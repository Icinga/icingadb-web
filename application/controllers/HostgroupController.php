<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Hostgroupsummary;
use Icinga\Module\Icingadb\Redis\VolatileStateResults;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\HostList;
use Icinga\Module\Icingadb\Widget\ItemList\HostgroupList;
use ipl\Html\Html;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\HorizontalKeyValue;

class HostgroupController extends Controller
{
    /** @var Hostgroupsummary The host group object */
    protected $hostgroup;

    public function init()
    {
        $this->assertRouteAccess('hostgroups');

        $this->setTitle(t('Host Group'));

        $name = $this->params->getRequired('name');

        $query = Hostgroupsummary::on($this->getDb());

        foreach ($query->getUnions() as $unionPart) {
            $unionPart->filter(Filter::equal('hostgroup.name', $name));
        }

        $this->applyRestrictions($query);

        $hostgroup = $query->first();
        if ($hostgroup === null) {
            throw new NotFoundError(t('Host group not found'));
        }

        $this->hostgroup = $hostgroup;
        $this->view->title = $hostgroup->display_name;
    }

    public function indexAction()
    {
        $db = $this->getDb();

        $hosts = Host::on($db)->with(['state', 'state.last_comment', 'icon_image'])->utilize('hostgroup');
        $hosts->setResultSetClass(VolatileStateResults::class);

        $hosts->getSelectBase()->where(['host_hostgroup.id = ?' => $this->hostgroup->id]);
        $this->applyRestrictions($hosts);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($hosts);
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);

        $hostList = (new HostList($hosts))
            ->setViewMode($viewModeSwitcher->getViewMode());

        yield $this->export($hosts);

        // ICINGAWEB_EXPORT_FORMAT is not set yet and $this->format is inaccessible, yeah...
        if ($this->getRequest()->getParam('format') === 'pdf') {
            $this->addContent((new HostgroupList([$this->hostgroup]))
                ->setViewMode('minimal')
                ->setDetailActionsDisabled()
                ->setNoSubjectLink());
            $this->addContent(Html::tag('h2', null, t('Hosts')));
        } else {
            $this->addControl((new HostgroupList([$this->hostgroup]))
                ->setViewMode('minimal')
                ->setDetailActionsDisabled()
                ->setNoSubjectLink());
        }

        $this->addControl($paginationControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($limitControl);

        $this->addContent($hostList);

        $this->setAutorefreshInterval(10);
    }
}
