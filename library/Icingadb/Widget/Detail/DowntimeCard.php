<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Model\Downtime;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Web\Compat\StyleWithNonce;
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

    protected $start;

    protected $end;

    public function __construct(Downtime $downtime)
    {
        $this->downtime = $downtime;

        $this->start = $this->downtime->scheduled_start_time->getTimestamp();
        $this->end = $this->downtime->scheduled_end_time->getTimestamp();

        if ($this->downtime->end_time && $this->downtime->end_time > $this->downtime->scheduled_end_time) {
            $this->duration = $this->downtime->end_time->getTimestamp() - $this->start;
        } else {
            $this->duration = $this->end - $this->start;
        }
    }

    protected function assemble()
    {
        $styleElement = (new StyleWithNonce())
            ->setModule('icingadb');

        $timeline = Html::tag('div', ['class' => 'downtime-timeline timeline']);
        $hPadding = 10;

        $above = Html::tag('ul', ['class' => 'above']);
        $below = Html::tag('ul', ['class' => 'below']);

        $markerStart = new HtmlElement('div', Attributes::create(['class' => ['marker' , 'left']]));
        $markerEnd = new HtmlElement('div', Attributes::create(['class' => ['marker', 'right']]));

        $timelineProgress = null;
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

            $markerFlexStart = new HtmlElement('div', Attributes::create(['class' => ['highlighted', 'marker']]));
            $markerFlexEnd = new HtmlElement('div', Attributes::create(['class' => ['highlighted', 'marker']]));

            $styleElement
                ->addFor($markerFlexStart, ['left' => sprintf('%F%%', $flexStartLeft)])
                ->addFor($markerFlexEnd, ['left' => sprintf('%F%%', $flexEndLeft)]);

            $scheduledEndBubble = new HtmlElement(
                'li',
                null,
                new HtmlElement(
                    'div',
                    Attributes::create(['class' => ['bubble', 'upwards']]),
                    new VerticalKeyValue(t('Scheduled End'), $endTime)
                )
            );

            $timelineProgress = new HtmlElement('div', Attributes::create([
                'class' => ['progress', 'downtime-elapsed'],
                'data-animate-progress' => true,
                'data-start-time' => ((float) $this->downtime->start_time->format('U.u')),
                'data-end-time' => ((float) $this->downtime->end_time->format('U.u'))
            ]), new HtmlElement(
                'div',
                Attributes::create(['class' => 'bar']),
                new HtmlElement('div', Attributes::create(['class' => 'now']))
            ));

            $styleElement->addFor($timelineProgress, [
                'left'  => sprintf('%F%%', $flexStartLeft),
                'width' => sprintf('%F%%', $flexEndLeft - $flexStartLeft)
            ]);

            if (time() > $this->end) {
                $styleElement
                    ->addFor($markerEnd, [
                        'left' => sprintf('%F%%', $hPadding + $this->calcRelativeLeft($this->end))
                    ])
                    ->addFor($scheduledEndBubble, [
                        'left' => sprintf('%F%%', $hPadding + $this->calcRelativeLeft($this->end))
                    ]);
            } else {
                $scheduledEndBubble->getAttributes()
                    ->add('class', 'right');
            }

            $below->add([
                Html::tag(
                    'li',
                    ['class' => 'left'],
                    Html::tag(
                        'div',
                        ['class' => ['bubble', 'upwards']],
                        new VerticalKeyValue(t('Scheduled Start'), new TimeAgo($this->start))
                    )
                ),
                $scheduledEndBubble
            ]);

            $aboveStart = Html::tag('li', ['class' => 'positioned'], Html::tag(
                'div',
                ['class' => ['bubble', ($evade ? 'left-aligned' : null)]],
                new VerticalKeyValue(t('Start'), new TimeAgo($this->downtime->start_time->getTimestamp()))
            ));

            $aboveEnd = Html::tag('li', ['class' => 'positioned'], Html::tag(
                'div',
                ['class' => ['bubble', ($evade ? 'right-aligned' : null)]],
                new VerticalKeyValue(t('End'), new TimeUntil($this->downtime->end_time->getTimestamp()))
            ));

            $styleElement
                ->addFor($aboveStart, ['left' => sprintf('%F%%', $flexStartLeft)])
                ->addFor($aboveEnd, ['left' => sprintf('%F%%', $flexEndLeft)]);

            $above->add([$aboveStart, $aboveEnd, $styleElement]);
        } elseif ($this->downtime->is_flexible) {
            $this->addAttributes(['class' => 'flexible']);

            $below->add([
                Html::tag(
                    'li',
                    ['class' => 'left'],
                    Html::tag(
                        'div',
                        ['class' => ['bubble', 'upwards']],
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
                    ['class' => 'right'],
                    Html::tag(
                        'div',
                        ['class' => ['bubble', 'upwards']],
                        new VerticalKeyValue(t('Scheduled End'), $endTime)
                    )
                )
            ]);

            $above = null;
        } else {
            if (time() >= $this->start) {
                $timelineProgress = new HtmlElement('div', Attributes::create([
                    'class' => ['progress', 'downtime-elapsed'],
                    'data-animate-progress' => true,
                    'data-start-time' => $this->start,
                    'data-end-time' => $this->end
                ]), new HtmlElement(
                    'div',
                    Attributes::create(['class' => 'bar']),
                    new HtmlElement('div', Attributes::create(['class' => 'now']))
                ));
            }

            $below->add([
                Html::tag(
                    'li',
                    ['class' => 'left'],
                    Html::tag(
                        'div',
                        ['class' => 'bubble upwards'],
                        new VerticalKeyValue(t('Start'), new TimeAgo($this->start))
                    )
                ),
                Html::tag(
                    'li',
                    ['class' => 'right'],
                    Html::tag(
                        'div',
                        ['class' => 'bubble upwards'],
                        new VerticalKeyValue(t('End'), new TimeUntil($this->end))
                    )
                )
            ]);

            $above = null;
        }

        $timeline->add([
            $timelineProgress,
            $flexProgress,
            $markerStart,
            $markerEnd,
            $markerFlexStart,
            $markerFlexEnd
        ]);

        $this->add([
            $above,
            $timeline,
            $below
        ]);
    }

    protected function calcRelativeLeft($value)
    {
        return round(($value - $this->start) / $this->duration * 80, 2);
    }
}
