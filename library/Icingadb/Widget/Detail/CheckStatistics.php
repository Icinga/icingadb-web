<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Date\DateFormatter;
use Icinga\Module\Icingadb\Widget\Card;
use Icinga\Module\Icingadb\Widget\CheckAttempt;
use Icinga\Module\Icingadb\Widget\TimeAgo;
use Icinga\Module\Icingadb\Widget\TimeSince;
use Icinga\Module\Icingadb\Widget\TimeUntil;
use Icinga\Module\Icingadb\Widget\VerticalKeyValue;
use Icinga\Util\Format;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\StateBall;

class CheckStatistics extends Card
{
    protected $object;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'check-statistics'];

    public function __construct($object)
    {
        $this->object = $object;
    }

    protected function assembleBody(BaseHtmlElement $body)
    {
        $hPadding = 10;
        $durationScale = 80;

        $timeline = Html::tag('div', ['class' => 'check-timeline timeline']);

        $overdueBar = null;
        $nextCheckTime = $this->object->state->next_check;
        if ($this->object->state->is_overdue) {
            $nextCheckTime = $this->object->state->next_update;
            $leftNow = $durationScale + $hPadding / 2;

            $overdueScale = ($durationScale / 2) * (time() - $nextCheckTime) / (10 * $this->object->check_interval);
            if ($overdueScale > $durationScale / 2) {
                $overdueScale = $durationScale / 2;
            }

            $durationScale -= $overdueScale;
            $overdueBar = Html::tag('div', [
                'class' => 'progress-bar overdue',
                'style' => sprintf(
                    'left: %F%%; width: %F%%;',
                    $hPadding + $durationScale,
                    $overdueScale + $hPadding / 2
                )
            ]);
        } else {
            $leftNow = $durationScale * (1 - ($nextCheckTime - time()) / $this->object->check_interval);
            if ($leftNow > $durationScale) {
                $leftNow = $durationScale;
            } elseif ($leftNow < 0) {
                $leftNow = 0;
            }
        }

        $above = Html::tag('ul', ['class' => 'above']);
        $now = Html::tag('li', [
            'class' => 'bubble now',
            'style' => sprintf('left: %F%%', $hPadding + $leftNow),
        ], Html::tag('strong', 'Now'));
        $above->add($now);

        $markerLast = Html::tag('div', [
            'class' => 'marker last',
            'style' => 'left: ' . $hPadding . '%',
            'title' => $this->object->state->last_update !== null
                ? DateFormatter::formatDateTime($this->object->state->last_update)
                : null
        ]);
        $markerNext = Html::tag('div', [
            'class' => 'marker next',
            'style' => sprintf('left: %F%%', $hPadding + $durationScale),
            'title' => $nextCheckTime !== null ? DateFormatter::formatDateTime($nextCheckTime) : null
        ]);
        $markerNow = Html::tag('div', [
            'class' => 'marker now',
            'style' => sprintf('left: %F%%', $hPadding + $leftNow),
        ]);

        $timeline->add([
            $markerLast,
            $markerNow,
            $markerNext,
            $overdueBar
        ]);

        $lastUpdate = Html::tag(
            'li',
            ['class' => 'bubble upwards last'],
            new VerticalKeyValue('Last update', $this->object->state->last_update !== null
                ? new TimeAgo($this->object->state->last_update)
                : 'PENDING')
        );
        $interval = Html::tag(
            'li',
            ['class' => 'interval'],
            new VerticalKeyValue('Interval', Format::seconds($this->object->check_interval))
        );
        $nextCheck = Html::tag(
            'li',
            ['class' => 'bubble upwards next'],
            $this->object->state->is_overdue
                ? new VerticalKeyValue('Overdue', new TimeSince($nextCheckTime))
                : new VerticalKeyValue(
                    'Next Check',
                    $nextCheckTime !== null ? new TimeUntil($nextCheckTime) : 'PENDING'
                )
        );

        $intervalLine = Html::tag('hr', ['class' => 'interval-line']);

        $bubbles = Html::tag(
            'ul',
            ['class' => 'below'],
            [$lastUpdate, $interval, $nextCheck]
        );

        $below = Html::tag(
            'div',
            [
                'class' => 'below-wrapper',
                'style' => sprintf('width: %F%%;', $durationScale)
            ],
            [$intervalLine, $bubbles]
        );

        $body->add([$above, $timeline, $below]);
    }

    protected function assembleFooter(BaseHtmlElement $footer)
    {
    }

    protected function assembleHeader(BaseHtmlElement $header)
    {
        $checkSource = [
            new StateBall($this->object->state->is_reachable ? 'up' : 'down', StateBall::SIZE_MEDIUM),
            ' ',
            $this->object->state->check_source
        ];

        $header->add([
            new VerticalKeyValue('Command', $this->object->checkcommand),
            new VerticalKeyValue(
                'Attempts',
                new CheckAttempt($this->object->state->attempt, $this->object->max_check_attempts)
            ),
            new VerticalKeyValue('Check source', $checkSource),
            new VerticalKeyValue('Execution time', Format::seconds($this->object->state->execution_time)),
            new VerticalKeyValue('Latency', Format::seconds($this->object->state->latency))
        ]);
    }
}
