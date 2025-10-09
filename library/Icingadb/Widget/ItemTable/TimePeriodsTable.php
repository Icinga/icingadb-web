<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\DetailActions;
use Icinga\Module\Icingadb\Common\Links;
use ipl\Html\Html;
use ipl\Html\Table;
use ipl\Web\Url;
use ipl\Web\Widget\ItemTable;
use ipl\Web\Widget\Link;

class TimePeriodsTable extends Table
{
    protected $defaultAttributes = ['class' => 'common-table'];

    protected Query $timeperiods;

    public function __construct($timeperiods)
    {
        $this->timeperiods = $timeperiods;
    }

    protected function assemble()
    {
        $tbody = $this->getBody();
        foreach ($this->timeperiods as $timeperiod) {
            $displayName = new Link($timeperiod->display_name, Url::fromPath('timeperiods/details', ['id' =>$timeperiod->id]));

            $displayName =  Html::tag('strong')->add($displayName->setBaseTarget('_next'));
            $r = Table::row([
                $displayName
            ]);
             $tbody->addHtml($r);

        }
    }

}
