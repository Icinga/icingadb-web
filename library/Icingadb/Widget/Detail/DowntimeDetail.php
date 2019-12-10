<?php

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Date\DateFormatter as WebDateFormatter;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\HostLinks;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Common\ServiceLinks;
use Icinga\Module\Icingadb\Date\DateFormatter;
use Icinga\Module\Icingadb\Model\Downtime;
use Icinga\Module\Icingadb\Widget\HorizontalKeyValue;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteDowntimeCommandForm;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlString;
use ipl\Web\Widget\Icon;

class DowntimeDetail extends BaseHtmlElement
{
    use Auth;
    use HostLink;
    use ServiceLink;

    /** @var  BaseHtmlElement */
    protected $control;

    /** @var Downtime */
    protected $downtime;

    /** @var string */
    protected $endTime;

    /** @var bool  */
    protected $isActive;

    /** @var string */
    protected $startTime;

    protected $defaultAttributes = ['class' => 'downtime-detail'];

    protected $tag = 'div';

    public function __construct($downtime)
    {
        $this->downtime = $downtime;

        if ($this->downtime->is_flexible && $this->downtime->is_in_effect) {
            $this->startTime = $this->downtime->start_time;
        } else {
            $this->startTime = $this->downtime->scheduled_start_time;
        }

        if ($this->downtime->is_flexible && $this->downtime->is_in_effect) {
            $this->endTime = $this->downtime->start_time + $this->downtime->flexible_duration;
        } else {
            $this->endTime = $this->downtime->scheduled_end_time;
        }

        $this->isActive = $this->downtime->is_in_effect
            || $this->downtime->is_flexible && $this->downtime->scheduled_start_time <= time();

        $this->duration = ($this->isActive ? $this->endTime : $this->startTime) - time();
    }

    public function getControl()
    {
        return Html::tag('ul', ['class' => 'downtime-detail item-list'], [
            Html::tag('li', ['class' => 'list-item'], [
                $this->createVisual(),
                $this->createMain()
            ])
        ]);
    }

    protected function createCancelDowntimeForm()
    {
        $formData = [
            'downtime_id'   => $this->downtime->name,
            'downtime_name' => $this->downtime->name,
            'redirect'     => '__BACK__'
        ];


        if ($this->downtime->object_type === 'host') {
            $action = HostLinks::cancelDowntime($this->downtime->host);
        } else {
            $action = ServiceLinks::cancelDowntime($this->downtime->service, $this->downtime->service->host);
            $formData['downtime_is_service'] = true;
        }

        $cancelDowntimeForm = (new DeleteDowntimeCommandForm())
            ->create()
            ->populate($formData)
            ->setAction($action);

        $submitButton = $cancelDowntimeForm->getElement('btn_submit');
        $submitButton->content = (new HtmlDocument())
            ->add([new Icon('trash'), 'Cancel Downtime'])
            ->setSeparator(' ')
            ->render();

        return new HtmlString($cancelDowntimeForm->render());
    }

    protected function createMain()
    {
        $main = Html::tag('div', ['class' => 'main']);
        $header = Html::tag('header');
        $title = Html::tag('div', ['class' =>'title']);

        if ($this->downtime->is_flexible) {
            $type = 'Flexible';
        } else {
            $type = 'Fixed';
        }

        if ($this->downtime->object_type === 'host') {
            $link = $this->createHostLink($this->downtime->host, true);
        } else {
            $link = $this->createServiceLink($this->downtime->service, $this->downtime->service->host, true);
        }

        $title->add([
            "{$type} Downtime",
            ': ',
            $link
        ]);

        $dateTime = WebDateFormatter::formatDateTime($this->endTime);
        $timestamp = Html::tag('time',
            [
                'datetime' => $dateTime,
                'title'    => $dateTime
            ],
            [
                $this->isActive ? 'expires in' : 'starts in',
                ' ',
                DateFormatter::formatDuration($this->duration)
            ]
        );

        $header->add($title);
        $header->add($timestamp);

        $main->add($header);

        return $main;
    }

    protected function createTimeline()
    {
        $ref = floor(
            (float) (time() - $this->startTime)
            / (float) ($this->endTime- $this->startTime)
            * 100
        );

        $timeline = Html::tag('div', ['class' => 'downtime-timeline']);

        $progress = Html::tag(
            'div',
            [
                'class' => 'progress-bar',
                'style' => 'width: ' . $ref . '%'
            ]
        );

        $timeline->add($progress);

        return $timeline;
    }

    protected function createVisual()
    {
        $visual = Html::tag('div', ['class' => 'visual']);
        $dateTime = WebDateFormatter::formatDateTime($this->endTime);

        if ($this->isActive) {
            if ($this->downtime->is_in_effect) {
                $visual->addAttributes(['class' => 'active']);
            }
            $visual->add([
                Html::tag(
                    'strong',
                    Html::tag(
                        'time',
                        [
                            'datetime' => $dateTime,
                            'title'    => $dateTime
                        ],
                        DateFormatter::formatDuration($this->duration, true)
                    )
                ),
                ' ',
                'left'
            ]);
        } else {
            $visual->add([
                'in',
                ' ',
                Html::tag('strong', DateFormatter::formatDuration($this->duration, true))
            ]);
        }

        return $visual;
    }

    protected function assemble()
    {
        $this->add(Html::tag('h2', 'Comment'));
        $this->add(Html::tag('div', [
            new Icon('user'),
            $this->downtime->author,
            ' commented:',
            Html::tag('br'),
            $this->downtime->comment
        ]));

        $this->add(Html::tag('h2', 'Details'));
        $this->add(
            new HorizontalKeyValue('Created', WebDateFormatter::formatDateTime($this->downtime->entry_time))
        );
        $this->add(
            new HorizontalKeyValue('Start time', WebDateFormatter::formatDateTime($this->downtime->start_time))
        );
        $this->add(
            new HorizontalKeyValue('End time', WebDateFormatter::formatDateTime($this->downtime->end_time)));
        $this->add(
            new HorizontalKeyValue(
                'Scheduled Start',
                WebDateFormatter::formatDateTime($this->downtime->scheduled_start_time)
            )
        );
        $this->add(
            new HorizontalKeyValue(
                'Scheduled End',
                WebDateFormatter::formatDateTime($this->downtime->scheduled_end_time)
            )
        );

        $this->add(Html::tag('h2', 'Progress'));
        $this->add($this->createTimeline());

        if ($this->getAuth()->hasPermission('monitoring/command/downtime/delete')) {
            $this->add($this->createCancelDowntimeForm());
        }
    }
}
