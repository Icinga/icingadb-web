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

        $this->start = $this->downtime->scheduled_start_time;
        $this->end = $this->downtime->scheduled_end_time;

        if ($this->downtime->end_time > $this->downtime->scheduled_end_time) {
            $this->duration = $this->downtime->end_time - $this->downtime->scheduled_start_time;
        } else {
            $this->duration = $this->downtime->scheduled_end_time - $this->downtime->scheduled_start_time;
        }
    }

    protected function assemble()
    {
        $timelineWrapper = Html::tag('div', ['class' => 'w-100 timeline-wrapper']);
        $hPadding = 10;

        $above = Html::tag('ul', ['class' => 'above']);
        $below = Html::tag('ul', ['class' => 'below']);

        $flexProgress = null;
        $markerFlexStart = null;
        $markerFlexEnd = null;
        $timeline = Html::tag('rect', [
            'class'  => 'timeline',
            'width'  => '100%',
            'height' => '10',
            'rx'     => '5',
            'x'      => '0',
            'y'      => '0'
        ]);
        $timelineProgress = Html::tag('rect', [
            'class'  => 'downtime-elapsed',
            'width'  => '0%',
            'height' => '10',
            'x'      => '0',
            'y'      => '0'
        ]);
        $svg = Html::tag('svg', ['class' => 'w-100']);

        if ($this->downtime->scheduled_end_time < time()) {
            $endTime = new TimeAgo($this->downtime->scheduled_end_time);
        } else {
            $endTime = new TimeUntil($this->downtime->scheduled_end_time);
        }

        if ($this->downtime->is_flexible && $this->downtime->is_in_effect) {
            $this->addAttributes(['class' => 'flexible in-effect']);

            $flexStartLeft = $hPadding + $this->calcRelativeLeft($this->downtime->start_time);
            $flexEndLeft = $hPadding + $this->calcRelativeLeft($this->downtime->end_time);

            $evade = false;
            if ($flexEndLeft - $flexStartLeft < 2) {
                $flexStartLeft -= 1;
                $flexEndLeft += 1;

                if ($flexEndLeft > $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_end_time)) {
                    $flexEndLeft = $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_end_time) - .5;
                    $flexStartLeft = $flexEndLeft - 2;
                }

                if ($flexStartLeft < $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_start_time)) {
                    $flexStartLeft = $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_start_time) + .5;
                    $flexEndLeft = $flexStartLeft + 2;
                }

                $evade = true;
            }

            $markerFlexStart = Html::tag('circle', [
                'class' => 'marker flex-start',
                'r'     => '5',
                'cx'    => $flexStartLeft . '%',
                'cy'    => '5'
            ]);

            $markerFlexEnd = Html::tag('circle', [
                'class' => 'marker flex-end',
                'r'     => '5',
                'cx'    => $flexEndLeft . '%',
                'cy'    => '5'
            ]);

            if (time() > $this->downtime->scheduled_end_time) {
                $timelineProgress
                    ->getAttributes()
                    ->set('x', sprintf('%F%%', $hPadding + $this->calcRelativeLeft($this->downtime->start_time)))
                    ->set(
                        'width',
                        sprintf(
                            '%F%%',
                            $this->calcRelativeLeft($this->downtime->scheduled_end_time, $this->downtime->start_time)
                        )
                    );

                $flexProgress = Html::tag('div', [
                    'class' => 'timeline-overlay downtime-overrun',
                    'style' => sprintf(
                        'left: %F%%; width: %F%%;',
                        $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_end_time),
                        $this->calcRelativeLeft(time(), $this->downtime->scheduled_end_time)
                    )
                ]);
            } else {
                $timelineProgress
                    ->getAttributes()
                    ->set('x', sprintf('%F%%', $hPadding + $this->calcRelativeLeft($this->downtime->start_time)))
                    ->set(
                        'width',
                        sprintf(
                            '%F%%',
                            $hPadding + $this->calcRelativeLeft(time()) - $flexStartLeft
                        )
                    );
            }


            $above->add([
                Html::tag(
                    'li',
                    ['class' => 'start positioned'],
                    Html::tag(
                        'div',
                        ['class' => 'bubble'],
                        new VerticalKeyValue(t('Scheduled Start'), new TimeAgo($this->downtime->scheduled_start_time))
                    )
                ),
                Html::tag(
                    'li',
                    [
                        'class' => 'end positioned',
                        'style' => sprintf(
                            'left: %F%%',
                            $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_end_time)
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
                        new VerticalKeyValue(t('Start'), new TimeAgo($this->downtime->start_time))
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
                        new VerticalKeyValue(t('End'), new TimeUntil($this->downtime->end_time))
                    )
                )
            ]);
        } elseif ($this->downtime->is_flexible) {
            $this->addAttributes(['class' => 'flexible']);

            $timelineProgress
                ->getAttributes()
                ->set('x', sprintf('%F%%', $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_start_time)))
                ->set(
                    'width',
                    sprintf(
                        '%F%%',
                        $this->calcRelativeLeft(time())
                    )
                );

            $above->add([
                Html::tag(
                    'li',
                    ['class' => 'start positioned'],
                    Html::tag(
                        'div',
                        ['class' => 'bubble'],
                        new VerticalKeyValue(
                            t('Scheduled Start'),
                            time() > $this->downtime->scheduled_start_time
                                ? new TimeAgo($this->downtime->scheduled_start_time)
                                : new TimeUntil($this->downtime->scheduled_start_time)
                        )
                    )
                ),
                Html::tag(
                    'li',
                    [
                        'class' => 'end positioned',
                        'style' => sprintf(
                            'left: %F%%',
                            $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_end_time)
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
            $timelineProgress
                ->getAttributes()
                ->set('x', sprintf('%F%%', $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_start_time)))
                ->set(
                    'width',
                    sprintf(
                        '%F%%',
                        $this->calcRelativeLeft(time())
                    )
                );

            $below->add([
                Html::tag(
                    'li',
                    [
                        'class' => 'start positioned',
                        'style' => sprintf(
                            'left: %F%%',
                            $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_start_time)
                        )
                    ],
                    Html::tag(
                        'div',
                        ['class' => 'bubble upwards'],
                        new VerticalKeyValue(t('Start'), new TimeAgo($this->downtime->scheduled_start_time))
                    )
                ),
                Html::tag(
                    'li',
                    [
                        'class' => 'end positioned',
                        'style' => sprintf(
                            'left: %F%%',
                            $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_end_time)
                        )
                    ],
                    Html::tag(
                        'div',
                        ['class' => 'bubble upwards'],
                        new VerticalKeyValue(t('End'), new TimeUntil($this->downtime->scheduled_end_time))
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

        $markerStart = Html::tag('circle', [
            'class' => 'marker start',
            'r'     => '5',
            'cx'    => $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_start_time) . '%',
            'cy'    => '5'
        ]);

        $markerNow = Html::tag('circle', [
            'class' => 'marker now',
            'r'     => '5',
            'cx'    => $hPadding + $this->calcRelativeLeft(time(), null, null, -$hPadding + 3) . '%',
            'cy'    => '5'
        ]);

        $markerEnd = Html::tag('circle', [
            'class' => 'marker end',
            'r'     => '5',
            'cx'    => $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_end_time) . '%',
            'cy'    => '5'
        ]);

        $startMarker = Html::tag('foreignObject', [
            'x' => $hPadding + $this->calcRelativeLeft(time(), null, null, -$hPadding + 3) . '%', 'y' => 15, 'width' => '250', 'height' => '100'
        ]);
        $bubble = Html::tag('xhtml:div', ['class' => 'bubble upwards']);
        $verticalKeyValue = Html::tag('xhtml:div', ['class' => 'vertical-key-value']);
        $timeValue = Html::tag('xhtml:span', ['class' => 'value']);
        $time = Html::tag('xhtml:time', ['class' => 'time-ago', 'datetime' => '2023-03-14 14:31:25', 'title' => '2023-03-14 14:31:25']);
        $textKey = Html::tag('span', ['class' => 'key']);
        $textKey->setContent('Start');
        $time->setContent('49m ago');

        $timeValue->add($time);
        $verticalKeyValue->add($timeValue);
        $verticalKeyValue->add($textKey);
        $bubble->add($verticalKeyValue);
        $startMarker->add($bubble);

        $svg->add([
            $timeline,
            $timelineProgress,
            $markerStart,
            $markerNow,
            $markerFlexStart,
            $markerFlexEnd,
            $markerEnd,
            $startMarker
        ]);

        $timelineWrapper->add([
            $svg,
        ]);

        $this->add([
            //   $above,
            $timelineWrapper,
            //   $below
        ]);
    }

    protected function calcRelativeLeft($value, $relativeStart = null, $relativeWidth = null, $min = null, $max = null)
    {
        if ($relativeStart === null) {
            $relativeStart = $this->downtime->scheduled_start_time;
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
