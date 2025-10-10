<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\Timeperiod;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemTable\TimePeriodsTable;
use ipl\Web\Compat\CompatController;

class TimeperiodsController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab('Time Periods');


        $db = $this->getDb();

        $query = Timeperiod::on($db);

        $this->addContent(new TimePeriodsTable($query));


    }
}

// Controller für alle Timeperiods zeigt Liste

//indexAction
//Liste; zeigt erst mal nur Display Name
