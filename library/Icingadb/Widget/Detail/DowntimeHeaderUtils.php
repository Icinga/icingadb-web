<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Date\DateFormatter;
use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Common\TicketLinks;
use Icinga\Module\Icingadb\Model\Downtime;
use Icinga\Module\Icingadb\Widget\MarkdownLine;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\TemplateString;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

trait DowntimeHeaderUtils
{
    use HostLink;
    use ServiceLink;
    use TicketLinks;
    use Translation;

    /** @var int Duration */
    protected $duration;

    /** @var int Downtime end time */
    protected $endTime;

    /** @var bool Whether the downtime is active */
    protected $isActive;

    /** @var int Downtime start time */
    protected $startTime;

    /** @var bool Whether the timestamps are initialised */
    protected $isPrepared;

    /**
     * Get the object
     *
     * @return Downtime
     */
    abstract protected function getObject(): Downtime;

    /**
     * Whether to create a subject link
     *
     * @return bool
     */
    abstract protected function wantSubjectLink(): bool;

    /**
     * Whether to create an object link
     *
     * @return bool
     */
    abstract protected function wantObjectLink(): bool;

    /**
     * Prepare the properties
     *
     * @return void
     */
    protected function prepare(): void
    {
        if ($this->isPrepared) {
            return;
        }

        $downtime = $this->getObject();
        if (isset($downtime->start_time, $downtime->end_time) && $downtime->is_flexible && $downtime->is_in_effect) {
            $this->startTime = $downtime->start_time->getTimestamp();
            $this->endTime = $downtime->end_time->getTimestamp();
        } else {
            $this->startTime = $downtime->scheduled_start_time->getTimestamp();
            $this->endTime = $downtime->scheduled_end_time->getTimestamp();
        }

        $currentTime = time();

        $this->isActive = ($downtime->is_in_effect //todo: fixed false positive by wrapping
            || $downtime->is_flexible) && $downtime->scheduled_start_time->getTimestamp() <= $currentTime;

        $until = ($this->isActive ? $this->endTime : $this->startTime) - $currentTime;
        $this->duration = explode(' ', DateFormatter::formatDuration(
            $until <= 3600 ? $until : $until + (3600 - ((int) $until % 3600))
        ), 2)[0];

        if ($downtime->is_in_effect) {
            $this->getAttributes()->add('class', 'in-effect');
        }

        $this->isPrepared = true;
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $this->prepare();
        $dateTime = DateFormatter::formatDateTime($this->endTime);

        if ($this->isActive) {
            $visual->addHtml(Html::sprintf(
                t('%s left', '<timespan>..'),
                Html::tag(
                    'strong',
                    Html::tag(
                        'time',
                        [
                            'datetime' => $dateTime,
                            'title'    => $dateTime
                        ],
                        $this->duration
                    )
                )
            ));
        } else {
            $visual->addHtml(Html::sprintf(
                t('in %s', '..<timespan>'),
                Html::tag('strong', $this->duration)
            ));
        }
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $downtime = $this->getObject();
        $link = null;
        if ($this->wantObjectLink()) {
            $link = $downtime->object_type === 'host'
                ? $this->createHostLink($downtime->host, true)
                : $this->createServiceLink($downtime->service, $downtime->service->host, true);
        }

        if ($downtime->is_flexible) {
            if ($link !== null) {
                $template = t('{{#link}}Flexible Downtime{{/link}} for %s');
            } else {
                $template = t('Flexible Downtime');
            }
        } else {
            if ($link !== null) {
                $template = t('{{#link}}Fixed Downtime{{/link}} for %s');
            } else {
                $template = t('Fixed Downtime');
            }
        }

        if (! $this->wantSubjectLink()) {
            if ($link === null) {
                $title->addHtml(HtmlElement::create('span', [ 'class' => 'subject'], $template));
            } else {
                $title->addHtml(TemplateString::create(
                    $template,
                    ['link' => HtmlElement::create('span', [ 'class' => 'subject'])],
                    $link
                ));
            }
        } else {
            if ($link === null) {
                $title->addHtml(new Link($template, Links::downtime($downtime)));
            } else {
                $title->addHtml(TemplateString::create(
                    $template,
                    ['link' => new Link('', Links::downtime($downtime))],
                    $link
                ));
            }
        }
    }

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $downtime = $this->getObject();
        $markdownLine = new MarkdownLine($this->createTicketLinks($downtime->comment));
        $caption->getAttributes()->add($markdownLine->getAttributes());
        $caption->addHtml(
            new HtmlElement(
                'span',
                null,
                new Icon(Icons::USER),
                Text::create($downtime->author)
            ),
            Text::create(': ')
        )->addFrom($markdownLine);
    }

    protected function createTimestamp(): ?BaseHtmlElement
    {
        $this->prepare();
        $dateTime = DateFormatter::formatDateTime($this->isActive ? $this->endTime : $this->startTime);

        return Html::tag(
            'time',
            [
                'datetime' => $dateTime,
                'title'    => $dateTime
            ],
            sprintf(
                $this->isActive
                    ? t('expires in %s', '..<timespan>')
                    : t('starts in %s', '..<timespan>'),
                $this->duration
            )
        );
    }
}
