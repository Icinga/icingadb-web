<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Model\TimeperiodRange;
use Icinga\Module\Icingadb\Widget\Detail\TimePeriodDetail;
use ipl\Stdlib\Filter;
use Icinga\Module\Icingadb\Model\Timeperiod;
use Icinga\Module\Icingadb\Web\Controller;


class TimeperiodController extends Controller
{
    public function indexAction(): void
    {
        $this->addTitleTab('Time Period');
        $timePeriodId = $this->params->getRequired('id');

        $timePeriod = Timeperiod::on($this->getDb())
            ->filter(Filter::equal('id', $timePeriodId))
            ->first();

        $ranges = TimeperiodRange::on($this->getDb())
            ->filter(Filter::equal('timeperiod_id', $timePeriodId));


            $this->addContent(new TimePeriodDetail($timePeriod, $ranges));

    }
}
