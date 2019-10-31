<?php

namespace Icinga\Module\Eagle\Controllers;

use Icinga\Module\Eagle\Model\Hostgroupsummary;
use Icinga\Module\Eagle\Web\Controller;
use Icinga\Module\Eagle\Widget\ItemList\HostgroupList;

class HostgroupsController extends Controller
{
    public function indexAction() {

        $this->setTitle($this->translate('Host Groups'));

        $db = $this->getDb();

        $hostgroups = Hostgroupsummary::on($db);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($hostgroups);
        $filterControl = $this->createFilterControl($hostgroups);

        $this->filter($hostgroups);

        yield $this->export($hostgroups);

        $this->addControl($paginationControl);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

        $this->addContent(new HostgroupList($hostgroups));
    }
}
