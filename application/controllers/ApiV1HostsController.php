<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Api\V1\ObjectsController;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Redis\VolatileStateResults;
use ipl\Stdlib\Filter;

class ApiV1HostsController extends ObjectsController
{
    protected function fetchCommandTargets()
    {
        $db = $this->getDb();

        $hosts = Host::on($db)->with('state');
        $hosts->setResultSetClass(VolatileStateResults::class);

        switch ($this->getRequest()->getActionName()) {
            case 'acknowledge':
                $hosts->filter(Filter::equal('state.is_problem', 'y'))
                    ->filter(Filter::equal('state.is_acknowledged', 'n'));

                break;
        }

        $this->filter($hosts);

        return $hosts;
    }
}
