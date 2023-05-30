<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Model\Downtime;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\TimeUntil;
use ipl\Web\Widget\VerticalKeyValue;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class DowntimeCard extends BaseHtmlElement
{
    protected $downtime;

    protected $duration;

    protected $defaultAttributes = ['class' => 'progress-bar downtime-progress'];

    protected $tag = 'div';

    public function __construct(Downtime $downtime)
    {
        $this->downtime = $downtime;

        $this->start = $this->downtime->scheduled_start_time->getTimestamp();
        $this->end = $this->downtime->scheduled_end_time->getTimestamp();

        if ($this->downtime->end_time > $this->downtime->scheduled_end_time) {
            $this->duration = $this->downtime->end_time->getTimestamp() - $this->start;
        } else {
            $this->duration = $this->end - $this->start;
        }
    }

    protected function assemble()
    {
        $timeline = Html::tag('div', ['class' => 'downtime-timeline timeline']);
        $hPadding = 10;

        $above = Html::tag('ul', ['class' => 'above']);
        $below = Html::tag('ul', ['class' => 'below']);

        $flexProgress = null;
        $markerFlexStart = null;
        $markerFlexEnd = null;

        if ($this->end < time()) {
            $endTime = new TimeAgo($this->end);
        } else {
            $endTime = new TimeUntil($this->end);
        }

        if ($this->downtime->is_flexible && $this->downtime->is_in_effect) {
            $this->addAttributes(['class' => 'flexible in-effect']);

            $flexStartLeft = $hPadding + $this->calcRelativeLeft($this->downtime->start_time->getTimestamp());
            $flexEndLeft = $hPadding + $this->calcRelativeLeft($this->downtime->end_time->getTimestamp());

            $evade = false;
            if ($flexEndLeft - $flexStartLeft < 2) {
                $flexStartLeft -= 1;
                $flexEndLeft += 1;

                if ($flexEndLeft > $hPadding + $this->calcRelativeLeft($this->end)) {
                    $flexEndLeft = $hPadding + $this->calcRelativeLeft($this->end) - .5;
                    $flexStartLeft = $flexEndLeft - 2;
                }

                if ($flexStartLeft < $hPadding + $this->calcRelativeLeft($this->start)) {
                    $flexStartLeft = $hPadding + $this->calcRelativeLeft($this->start) + .5;
                    $flexEndLeft = $flexStartLeft + 2;
                }

                $evade = true;
            }

            $markerFlexStart = Html::tag('div', [
                'class' => 'marker flex-start',
                'style' => sprintf('left: %F%%', $flexStartLeft)
            ]);

            $markerFlexEnd = Html::tag('div', [
                'class' => 'marker flex-end',
                'style' => sprintf('left: %F%%', $flexEndLeft)
            ]);

            if (time() > $this->end) {
                $timelineProgress = Html::tag('div', [
                    'class' => 'timeline-overlay downtime-elapsed',
                    'style' => sprintf(
                        'left: %F%%; width: %F%%;',
                        $hPadding + $this->calcRelativeLeft($this->downtime->start_time->getTimestamp()),
                        $this->calcRelativeLeft($this->end, $this->downtime->start_time->getTimestamp())
                    )
                ]);
                $flexProgress = Html::tag('div', [
                    'class' => 'timeline-overlay downtime-overrun',
                    'style' => sprintf(
                        'left: %F%%; width: %F%%;',
                        $hPadding + $this->calcRelativeLeft($this->end),
                        $this->calcRelativeLeft(time(), $this->end)
                    )
                ]);
            } else {
                $timelineProgress = Html::tag('div', [
                    'class' => 'timeline-overlay downtime-elapsed',
                    'style' => sprintf(
                        'left: %F%%; width: %F%%;',
                        $flexStartLeft,
                        $hPadding + $this->calcRelativeLeft(time()) - $flexStartLeft
                    )
                ]);
            }

            $above->add([
                Html::tag(
                    'li',
                    ['class' => 'start positioned'],
                    Html::tag(
                        'div',
                        ['class' => 'bubble'],
                        new VerticalKeyValue(t('Scheduled Start'), new TimeAgo($this->start))
                    )
                ),
                Html::tag(
                    'li',
                    [
                        'class' => 'end positioned',
                        'style' => sprintf(
                            'left: %F%%',
                            $hPadding + $this->calcRelativeLeft($this->end)
                        )
                    ],
                    Html::tag('div', ['class' => 'bubble'], new VerticalKeyValue(t('Scheduled End'), $endTime))
                )
            ]);

            $below->add([
                Html::tag(
                    'li',
                    [
                        'class' => 'start positioned',
                        'style' => sprintf('left: %F%%', $flexStartLeft)
                    ],
                    Html::tag(
                        'div',
                        ['class' => 'bubble upwards' . ($evade ? ' left' : '')],
                        new VerticalKeyValue(t('Start'), new TimeAgo($this->downtime->start_time->getTimestamp()))
                    )
                ),
                Html::tag(
                    'li',
                    [
                        'class' => 'end positioned',
                        'style' => sprintf('left: %F%%', $flexEndLeft)
                    ],
                    Html::tag(
                        'div',
                        ['class' => 'bubble upwards' . ($evade ? ' right' : '')],
                        new VerticalKeyValue(t('End'), new TimeUntil($this->downtime->end_time->getTimestamp()))
                    )
                )
            ]);
        } elseif ($this->downtime->is_flexible) {
            $this->addAttributes(['class' => 'flexible']);

            $timelineProgress = Html::tag('div', [
                'class' => 'timeline-overlay downtime-elapsed',
                'style' => sprintf(
                    'left: %F%%; width: %F%%;',
                    $hPadding + $this->calcRelativeLeft($this->start),
                    $this->calcRelativeLeft(time())
                )
            ]);

            $above->add([
                Html::tag(
                    'li',
                    ['class' => 'start positioned'],
                    Html::tag(
                        'div',
                        ['class' => 'bubble'],
                        new VerticalKeyValue(
                            t('Scheduled Start'),
                            time() > $this->start
                                ? new TimeAgo($this->start)
                                : new TimeUntil($this->start)
                        )
                    )
                ),
                Html::tag(
                    'li',
                    [
                        'class' => 'end positioned',
                        'style' => sprintf(
                            'left: %F%%',
                            $hPadding + $this->calcRelativeLeft($this->end)
                        )
                    ],
                    Html::tag(
                        'div',
                        ['class' => 'bubble'],
                        new VerticalKeyValue(t('Scheduled End'), $endTime)
                    )
                )
            ]);

            $below = null;
        } else {
            $timelineProgress = Html::tag('div', [
                'class' => 'timeline-overlay downtime-elapsed',
                'style' => sprintf(
                    'left: %F%%; width: %F%%;',
                    $hPadding + $this->calcRelativeLeft($this->start),
                    $this->calcRelativeLeft(time())
                )
            ]);

            $below->add([
                Html::tag(
                    'li',
                    [
                        'class' => 'start positioned',
                        'style' => sprintf(
                            'left: %F%%',
                            $hPadding + $this->calcRelativeLeft($this->start)
                        )
                    ],
                    Html::tag(
                        'div',
                        ['class' => 'bubble upwards'],
                        new VerticalKeyValue(t('Start'), new TimeAgo($this->start))
                    )
                ),
                Html::tag(
                    'li',
                    [
                        'class' => 'end positioned',
                        'style' => sprintf(
                            'left: %F%%',
                            $hPadding + $this->calcRelativeLeft($this->end)
                        )
                    ],
                    Html::tag(
                        'div',
                        ['class' => 'bubble upwards'],
                        new VerticalKeyValue(t('End'), new TimeUntil($this->end))
                    )
                )
            ]);
        }

        $now = Html::tag(
            'li',
            [
                'class' => 'now positioned',
                'style' => sprintf(
                    'left: %F%%',
                    $hPadding + $this->calcRelativeLeft(time(), null, null, -$hPadding + 3)
                )
            ],
            Html::tag(
                'div',
                ['class' => 'bubble'],
                Html::tag('strong', t('Now'))
            )
        );
        $above->add($now);

        $markerStart = Html::tag('div', [
            'class' => 'marker start',
            'style' => sprintf(
                'left: %F%%',
                $hPadding + $this->calcRelativeLeft($this->start)
            )
        ]);

        $markerNow = Html::tag('div', [
            'class' => 'marker now',
            'style' => sprintf(
                'left: %F%%',
                $hPadding + $this->calcRelativeLeft(time(), null, null, -$hPadding + 3)
            )
        ]);

        $markerEnd = Html::tag('div', [
            'class' => 'marker end',
            'style' => sprintf(
                'left: %F%%',
                $hPadding + $this->calcRelativeLeft($this->end)
            )
        ]);

        $timeline->add([
            $timelineProgress,
            $flexProgress,
            $markerStart,
            $markerEnd,
            $markerFlexStart,
            $markerFlexEnd,
            $markerNow,
        ]);

        $this->add([
            $above,
            $timeline,
            $below
        ]);
    }

    protected function calcRelativeLeft($value, $relativeStart = null, $relativeWidth = null, $min = null, $max = null)
    {
        if ($relativeStart === null) {
            $relativeStart = $this->start;
        }

        if ($relativeWidth === null) {
            $relativeWidth = $this->duration;
        }

        $left = round(($value - $relativeStart) / $relativeWidth * 80, 2);

        if ($min !== null && $left < $min) {
            $left = $min;
        }

        if ($max !== null && $left > $max) {
            $left = $max;
        }

        return $left;
    }
}
