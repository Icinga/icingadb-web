<?php
/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use ipl\Html\Table;
use ipl\Orm\Model;

/**
 *
 */
class TimePeriodDetailsTable extends Table
{

    protected $defaultAttributes = ['class' => 'common-table'];

    protected Model $timePeriod;

    public function __construct(Model $timePeriod)
    {
        $this->timePeriod = $timePeriod;
    }

    protected function assemble(): void
    {
        $this->getHeader()->addHtml(self::row([
            $this->timePeriod->display_name,
            ], null, 'th'));

        $tbody = $this->getBody();

        $tbody->addHtml(self::row([

            $this->timePeriod->display_name,
            $this->timePeriod->name,
        ]));
    }
}
