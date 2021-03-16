<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Date\DateFormatter;
use Icinga\Date\DateFormatter as WebDateFormatter;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\MarkdownText;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Forms\Command\Object\DeleteDowntimeForm;
use Icinga\Module\Icingadb\Model\Downtime;
use Icinga\Module\Icingadb\Widget\HorizontalKeyValue;
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

    /** @var BaseHtmlElement */
    protected $control;

    /** @var Downtime */
    protected $downtime;

    /** @var int */
    protected $duration;

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

    protected function createCancelDowntimeForm()
    {
        $action = Links::downtimesDelete();
        $action->setParam('name', $this->downtime->name);

        return (new DeleteDowntimeForm())
            ->setObjects([$this->downtime])
            ->populate(['redirect' => '__BACK__'])
            ->setAction($action->getAbsoluteUrl());
    }

    protected function createTimeline()
    {
        return new DowntimeCard($this->downtime);
    }

    protected function assemble()
    {
        $this->add(Html::tag('h2', t('Comment')));
        $this->add(Html::tag('div', [
            new Icon('user'),
            Html::sprintf(
                t('%s commented: %s', '<username> ..: <comment>'),
                $this->downtime->author,
                new MarkdownText($this->downtime->comment)
            )
        ]));

        $this->add(Html::tag('h2', t('Details')));
        $this->add(new HorizontalKeyValue(
            t('Created'),
            WebDateFormatter::formatDateTime($this->downtime->entry_time)
        ));
        $this->add(new HorizontalKeyValue(
            t('Start time'),
            WebDateFormatter::formatDateTime($this->downtime->start_time)
        ));
        $this->add(new HorizontalKeyValue(
            t('End time'),
            WebDateFormatter::formatDateTime($this->downtime->end_time)
        ));
        $this->add(new HorizontalKeyValue(
            t('Scheduled Start'),
            WebDateFormatter::formatDateTime($this->downtime->scheduled_start_time)
        ));
        $this->add(new HorizontalKeyValue(
            t('Scheduled End'),
            WebDateFormatter::formatDateTime($this->downtime->scheduled_end_time)
        ));
        $this->add(new HorizontalKeyValue(
            t('Scheduled Duration'),
            DateFormatter::formatDuration(
                $this->downtime->scheduled_end_time - $this->downtime->scheduled_start_time
            )
        ));
        if ($this->downtime->is_flexible) {
            $this->add(new HorizontalKeyValue(
                t('Flexible Duration'),
                DateFormatter::formatDuration($this->downtime->flexible_duration)
            ));
        }

        $this->add(Html::tag('h2', t('Progress')));
        $this->add($this->createTimeline());

        if (
            $this->isGrantedOn(
                'icingadb/command/downtime/delete',
                $this->downtime->{$this->downtime->object_type}
            )
        ) {
            $this->add($this->createCancelDowntimeForm());
        }
    }
}
