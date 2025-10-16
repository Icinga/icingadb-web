<?php
/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use ipl\Html\HtmlElement;
use ipl\Html\Table;
use ipl\Html\Text;
use ipl\Orm\Model;
use ipl\Orm\Query;

use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\HorizontalKeyValue;

/**
 *
 */
class TimePeriodDetail extends Table
{

    protected $defaultAttributes = ['class' => 'common-table'];

    protected Model $timePeriod;
    protected Query $range;

    public function __construct(Model $timePeriod, Query $range)
    {
        $this->timePeriod = $timePeriod;
        $this->range = $range;
    }

    protected function assemble(): void
    {
        $this->getHeader()->addHtml(self::row([
//            new Ball(Ball::SIZE_BIG),
            $this->timePeriod->display_name,
            $this->timePeriod->name,
            ], null, 'th'));
        $tbody = $this->getBody();

        $this->addHtml( new HtmlElement('h2', null, Text::create(t('Ranges'))));

        foreach ($this->range as $r) {
            $detail = [
                new HorizontalKeyValue(t('Day'), $r->range_key),
                new HorizontalKeyValue(t('Time'), $r->range_value),
            ];

            $tbody->addHtml(self::row([
                $detail
            ]));
        }

        if (empty($detail)) {
            $this->addHtml(new EmptyState('No ranges have been configured'));
        }
    }
}
