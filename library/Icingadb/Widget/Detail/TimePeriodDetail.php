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
    protected Query $ranges;

    public function __construct(Model $timePeriod, Query $range)
    {
        $this->timePeriod = $timePeriod;
        $this->ranges = $range;
    }

    protected function sortDays(array $days): array
    {
        $dayMap = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $flippedDayMap = array_flip($dayMap);

        uasort($days, function ($a, $b) use ($flippedDayMap) {
            $ia = $flippedDayMap[$a] ?? PHP_INT_MAX;
            $ib = $flippedDayMap[$b] ?? PHP_INT_MAX;
            return $ia <=> $ib;

        });
        return $days;
    }

    protected function assemble(): void
    {
        $this->getHeader()->addHtml(self::row([
//            new Ball(Ball::SIZE_BIG),
            $this->timePeriod->display_name,
            $this->timePeriod->name,
        ], null, 'th'));
        $tbody = $this->getBody();

        $this->addHtml(new HtmlElement('h2', null, Text::create(t('Ranges'))));

        foreach ($this->ranges as $range) {
            $days[] = $range->range_key;
        }
        if (empty($days)) {
            $this->addHtml(new EmptyState('No ranges have been configured'));
        } else {
            $weekDays = $this->sortDays($days);

            foreach ($this->ranges as $range) {
                $timeRanges[] = $range->range_value;
            }

            $results = [];
            foreach ($weekDays as $key => $day) {
                $results[$day] = $timeRanges[$key];
            }

            foreach ($results as $day => $time) {
                $rangeDayAndTime = [
                    new HorizontalKeyValue(t('Day'), $day),
                    new HorizontalKeyValue(t('Time'), $time),
                ];

                $tbody->addHtml(self::row([
                    $rangeDayAndTime
                ]));
            }
        }
    }
}
