<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Model\Downtime;
use ipl\Html\HtmlDocument;
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

    protected $nonce;

    public function __construct(Downtime $downtime, string $nonce)
    {
        $this->downtime = $downtime;
        $this->nonce = $nonce;

        $this->start = $this->downtime->scheduled_start_time;
        $this->end = $this->downtime->scheduled_end_time;

        if ($this->downtime->end_time > $this->downtime->scheduled_end_time) {
            $this->duration = $this->downtime->end_time - $this->downtime->scheduled_start_time;
        } else {
            $this->duration = $this->downtime->scheduled_end_time - $this->downtime->scheduled_start_time;
        }
    }

    private function styleAppend(HtmlDocument $element, string $selector, string $body): void
    {
        $content = '';
        foreach ($element->getContent() as $c) {
            $content .= $c->render();
        }

        $element->setContent(sprintf('%s %s { %s }', $content, $selector, $body));
    }

    protected function assemble()
    {
        $timeline = Html::tag('div', ['class' => 'downtime-timeline timeline']);
        $hPadding = 10;

        $style = Html::tag('style', ['nonce' => $this->nonce]);

        $above = Html::tag('ul', ['class' => 'above']);
        $below = Html::tag('ul', ['class' => 'below']);

        $flexProgress = null;
        $markerFlexStart = null;
        $markerFlexEnd = null;

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

            $markerFlexStart = Html::tag('div', ['class' => 'marker flex-start flex-start-dyn']);
            $this->styleAppend($style, '.flex-start-dyn', sprintf('left: %F%%', $flexStartLeft));

            $markerFlexEnd = Html::tag('div', ['class' => 'marker flex-end flex-end-dyn']);
            $this->styleAppend($style, '.flex-end-dyn', sprintf('left: %F%%', $flexEndLeft));

            if (time() > $this->downtime->scheduled_end_time) {
                $timelineProgress = Html::tag(
                    'div',
                    ['class' => 'timeline-overlay downtime-elapsed downtime-elapsed-dyn']
                );
                $this->styleAppend($style, '.downtime-elapsed-dyn', sprintf(
                    'left: %F%%; width: %F%%;',
                    $hPadding + $this->calcRelativeLeft($this->downtime->start_time),
                    $this->calcRelativeLeft($this->downtime->scheduled_end_time, $this->downtime->start_time)
                ));

                $flexProgress = Html::tag('div', ['class' => 'timeline-overlay downtime-overrun downtime-overrun-dyn']);

                $this->styleAppend($style, '.downtime-overrun-dyn', sprintf(
                    'left: %F%%; width: %F%%;',
                    $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_end_time),
                    $this->calcRelativeLeft(time(), $this->downtime->scheduled_end_time)
                ));
            } else {
                $timelineProgress = Html::tag(
                    'div',
                    ['class' => 'timeline-overlay downtime-elapsed downtime-elapsed-dyn']
                );

                $this->styleAppend($style, '.downtime-elapsed-dyn', sprintf(
                    'left: %F%%; width: %F%%;',
                    $flexStartLeft,
                    $hPadding + $this->calcRelativeLeft(time()) - $flexStartLeft
                ));
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
                    ['class' => 'end positioned end-positioned-bubble-dyn'],
                    Html::tag('div', ['class' => 'bubble'], new VerticalKeyValue(t('Scheduled End'), $endTime))
                )
            ]);

            $this->styleAppend($style, '.end-positioned-bubble-dyn', sprintf(
                'left: %F%%',
                $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_end_time)
            ));

            $below->add([
                Html::tag(
                    'li',
                    ['class' => 'start positioned start-positioned-dyn'],
                    Html::tag(
                        'div',
                        ['class' => 'bubble upwards' . ($evade ? ' left' : '')],
                        new VerticalKeyValue(t('Start'), new TimeAgo($this->downtime->start_time))
                    )
                ),
                Html::tag(
                    'li',
                    ['class' => 'end positioned end-positioned-dyn'],
                    Html::tag(
                        'div',
                        ['class' => 'bubble upwards' . ($evade ? ' right' : '')],
                        new VerticalKeyValue(t('End'), new TimeUntil($this->downtime->end_time))
                    )
                )
            ]);

            $this->styleAppend($style, '.start-positioned-dyn', sprintf('left: %F%%', $flexStartLeft));
            $this->styleAppend($style, '.end-positioned-dyn', sprintf('left: %F%%', $flexEndLeft));
        } elseif ($this->downtime->is_flexible) {
            $this->addAttributes(['class' => 'flexible']);

            $timelineProgress = Html::tag('div', ['class' => 'timeline-overlay downtime-elapsed downtime-elapsed-dyn']);
            $this->styleAppend($style, '.downtime-elapsed-dyn', sprintf(
                'left: %F%%; width: %F%%;',
                $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_start_time),
                $this->calcRelativeLeft(time())
            ));

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
                    ['class' => 'end positioned end-positioned-dyn',],
                    Html::tag(
                        'div',
                        ['class' => 'bubble'],
                        new VerticalKeyValue(t('Scheduled End'), $endTime)
                    )
                )
            ]);

            $this->styleAppend($style, '.end-positioned-dyn', sprintf(
                'left: %F%%',
                $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_end_time)
            ));

            $below = null;
        } else {
            $timelineProgress = Html::tag('div', ['class' => 'timeline-overlay downtime-elapsed downtime-elapsed-dyn']);

            $this->styleAppend($style, '.downtime-elapsed-dyn', sprintf(
                'left: %F%%; width: %F%%;',
                $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_start_time),
                $this->calcRelativeLeft(time())
            ));

            $below->add([
                Html::tag(
                    'li',
                    ['class' => 'start positioned start-positioned-dyn'],
                    Html::tag(
                        'div',
                        ['class' => 'bubble upwards'],
                        new VerticalKeyValue(t('Start'), new TimeAgo($this->downtime->scheduled_start_time))
                    )
                ),
                Html::tag(
                    'li',
                    ['class' => 'end positioned end-positioned-dyn'],
                    Html::tag(
                        'div',
                        ['class' => 'bubble upwards'],
                        new VerticalKeyValue(t('End'), new TimeUntil($this->downtime->scheduled_end_time))
                    )
                )
            ]);

            $this->styleAppend($style, '.start-positioned-dyn', sprintf(
                'left: %F%%',
                $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_start_time)
            ));

            $this->styleAppend($style, '.end-positioned-dyn', sprintf(
                'left: %F%%',
                $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_end_time)
            ));
        }

        $now = Html::tag(
            'li',
            ['class' => 'now positioned now-positioned-dyn'],
            Html::tag(
                'div',
                ['class' => 'bubble'],
                Html::tag('strong', t('Now'))
            )
        );
        $this->styleAppend($style, '.now-positioned-dyn', sprintf(
            'left: %F%%',
            $hPadding + $this->calcRelativeLeft(time(), null, null, -$hPadding + 3)
        ));

        $above->add($now);

        $markerStart = Html::tag('div', ['class' => 'marker start marker-start-dyn']);
        $this->styleAppend($style, '.marker-start-dyn', sprintf(
            'left: %F%%',
            $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_start_time)
        ));

        $markerNow = Html::tag('div', ['class' => 'marker now marker-now-dyn']);
        $this->styleAppend($style, '.marker-now-dyn', sprintf(
            'left: %F%%',
            $hPadding + $this->calcRelativeLeft(time(), null, null, -$hPadding + 3)
        ));

        $markerEnd = Html::tag('div', ['class' => 'marker end marker-end-dyn']);
        $this->styleAppend($style, '.marker-end-dyn', sprintf(
            'left: %F%%',
            $hPadding + $this->calcRelativeLeft($this->downtime->scheduled_end_time)
        ));

        $timeline->add([
            $timelineProgress,
            $flexProgress,
            $markerStart,
            $markerEnd,
            $markerFlexStart,
            $markerFlexEnd,
            $markerNow
        ]);

        $this->add([
            $style,
            $above,
            $timeline,
            $below
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
