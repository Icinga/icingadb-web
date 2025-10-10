<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Widget\Detail\TimePeriodDetailsTable;
use ipl\Stdlib\Filter;
use Icinga\Module\Icingadb\Model\Timeperiod;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemTable\TimePeriodsTable;
use ipl\Web\Compat\CompatController;

class TimeperiodController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab('Time Period');
        $timePeriodId = $this->params->getRequired('id');

        $timePeriod = Timeperiod::on($this->getDb())
            ->filter(Filter::equal('id', $timePeriodId))
            ->first();

//        $query = Timeperiod::on($db);

        $this->addContent(new TimePeriodDetailsTable($timePeriod));

    }
}


// Controller für details zu einer Timeperiod

//indexAction
//
//Detail; zeigt Display Name oben im Header, Name und Ranges im Content
