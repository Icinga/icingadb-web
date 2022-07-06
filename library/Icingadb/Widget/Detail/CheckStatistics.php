<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Date\DateFormatter;
use Icinga\Module\Icingadb\Widget\CheckAttempt;
use Icinga\Module\Icingadb\Widget\EmptyState;
use Icinga\Util\Format;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Common\Card;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\TimeSince;
use ipl\Web\Widget\TimeUntil;
use ipl\Web\Widget\VerticalKeyValue;

class CheckStatistics extends Card
{
    protected $object;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'progress-bar check-statistics'];

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
                'class' => 'timeline-overlay check-overdue',
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
        $now = Html::tag(
            'li',
            [
                'class' => 'now positioned',
                'style' => sprintf('left: %F%%', $hPadding + $leftNow)
            ],
            Html::tag(
                'div',
                ['class' => 'bubble'],
                Html::tag(
                    'strong',
                    t('Now')
                )
            )
        );
        $above->add($now);

        $markerLast = Html::tag('div', [
            'class' => 'marker start',
            'style' => 'left: ' . $hPadding . '%',
            'title' => $this->object->state->last_update !== null
                ? DateFormatter::formatDateTime($this->object->state->last_update)
                : null
        ]);
        $markerNext = Html::tag('div', [
            'class' => 'marker end',
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
            ['class' => 'start'],
            Html::tag(
                'div',
                ['class' => 'bubble upwards'],
                new VerticalKeyValue(t('Last update'), $this->object->state->last_update !== null
                    ? new TimeAgo($this->object->state->last_update)
                    : t('PENDING'))
            )
        );
        $interval = Html::tag(
            'li',
            ['class' => 'interval'],
            new VerticalKeyValue(
                t('Interval'),
                $this->object->check_interval
                    ? Format::seconds($this->object->check_interval)
                    : (new EmptyState(t('n. a.')))->setTag('span')
            )
        );
        $nextCheck = Html::tag(
            'li',
            ['class' => 'end'],
            Html::tag(
                'div',
                ['class' => 'bubble upwards'],
                $this->object->state->is_overdue
                    ? new VerticalKeyValue(t('Overdue'), new TimeSince($nextCheckTime))
                    : new VerticalKeyValue(
                        t('Next Check'),
                        $nextCheckTime !== null ? new TimeUntil($nextCheckTime) : t('PENDING')
                    )
            )
        );

        $below = Html::tag(
            'ul',
            [
                'class' => 'below',
                'style' => sprintf('width: %F%%;', $durationScale)
            ]
        );
        $below->add([
            $lastUpdate,
            $interval,
            $nextCheck
        ]);

        $body->add([$above, $timeline, $below]);
    }

    protected function assembleFooter(BaseHtmlElement $footer)
    {
        $footer->add(new HorizontalKeyValue(
            t('Scheduling Source') . ':',
            $this->object->state->scheduling_source ?? (new EmptyState(t('n. a.')))->setTag('span')
        ));
    }

    protected function assembleHeader(BaseHtmlElement $header)
    {
        $checkSource = (new EmptyState(t('n. a.')))->setTag('span');
        if ($this->object->state->check_source) {
            $checkSource = [
                new StateBall($this->object->state->is_reachable ? 'up' : 'down', StateBall::SIZE_MEDIUM),
                ' ',
                $this->object->state->check_source
            ];
        }

        $header->add([
            new VerticalKeyValue(t('Command'), $this->object->checkcommand_name),
            new VerticalKeyValue(
                t('Attempts'),
                new CheckAttempt((int) $this->object->state->check_attempt, (int) $this->object->max_check_attempts)
            ),
            new VerticalKeyValue(t('Check Source'), $checkSource),
            new VerticalKeyValue(
                t('Execution time'),
                $this->object->state->execution_time
                    ? Format::seconds($this->object->state->execution_time)
                    : (new EmptyState(t('n. a.')))->setTag('span')
            ),
            new VerticalKeyValue(
                t('Latency'),
                $this->object->state->latency
                    ? Format::seconds($this->object->state->latency)
                    : (new EmptyState(t('n. a.')))->setTag('span')
            )
        ]);
    }
}
