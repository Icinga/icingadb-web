<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\DetailActions;
use Icinga\Module\Icingadb\Common\Links;
use ipl\Html\Html;
use ipl\Html\Table;
use ipl\Orm\Query;
use ipl\Web\Url;
use ipl\Web\Widget\ItemTable;
use ipl\Web\Widget\Link;

class TimePeriodsTable extends Table
{
    protected $defaultAttributes = ['class' => 'common-table'];

    protected Query $timePeriods;

    public function __construct($timePeriods)
    {
        $this->timePeriods = $timePeriods;
    }

    protected function assemble()
    {
        $tbody = $this->getBody();

        foreach ($this->timePeriods as $timePeriod) {
            $displayName = new Link($timePeriod->display_name, Url::fromPath('icingadb/timeperiod/index', ['id' =>$timePeriod->id]));

            $displayName =  Html::tag('strong')->add($displayName->setBaseTarget('_next'));
            $r = Table::row([
                $displayName
//                hier muss noch rage rein!
            ]);
             $tbody->addHtml($r);

        }
    }

}
