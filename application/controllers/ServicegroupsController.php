<?php

namespace Icinga\Module\Eagle\Controllers;

use Icinga\Module\Eagle\Model\Servicegroupsummary;
use Icinga\Module\Eagle\Web\Controller;
use Icinga\Module\Eagle\Widget\ItemList\ServicegroupList;

class ServicegroupsController extends Controller
{
    public function indexAction() {

        $this->setTitle($this->translate('Service Groups'));

        $db = $this->getDb();

        $servicegroups = Servicegroupsummary::on($db);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($servicegroups);
        $filterControl = $this->createFilterControl($servicegroups);

        $this->filter($servicegroups);

        yield $this->export($servicegroups);

        $this->addControl($paginationControl);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

        $this->addContent(new ServicegroupList($servicegroups));
    }
}
