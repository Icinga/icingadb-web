<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\ServicegroupSummary;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\ServicegroupList;

class ServicegroupsController extends Controller
{
    public function indexAction() {

        $this->setTitle($this->translate('Service Groups'));

        $db = $this->getDb();

        $servicegroups = ServicegroupSummary::on($db);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($servicegroups);
        $filterControl = $this->createFilterControl($servicegroups);

        $this->filter($servicegroups);

        yield $this->export($servicegroups);

        $this->addControl($paginationControl);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

        $this->addContent(
            (new ServicegroupList($servicegroups))->setBaseFilter($this->getFilter())
        );
    }
}
