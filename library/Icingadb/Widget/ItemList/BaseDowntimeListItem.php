<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Date\DateFormatter;
use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Common\ObjectLinkDisabled;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Common\TicketLinks;
use Icinga\Module\Icingadb\Model\Downtime;
use Icinga\Module\Icingadb\Widget\MarkdownLine;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\TemplateString;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Web\Common\BaseListItem;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

/**
 * Downtime item of a downtime list. Represents one database row.
 *
 * @property Downtime $item
 * @property DowntimeList $list
 */
abstract class BaseDowntimeListItem extends BaseListItem
{
    use HostLink;
    use ServiceLink;
    use NoSubjectLink;
    use ObjectLinkDisabled;
    use TicketLinks;

    /** @var int Current Time */
    protected $currentTime;

    /** @var int Duration */
    protected $duration;

    /** @var int Downtime end time */
    protected $endTime;

    /** @var bool Whether the downtime is active */
    protected $isActive;

    /** @var int Downtime start time */
    protected $startTime;

    protected function init(): void
    {
        if ($this->item->is_flexible && $this->item->is_in_effect) {
            $this->startTime = $this->item->start_time->getTimestamp();
            $this->endTime = $this->item->end_time->getTimestamp();
        } else {
            $this->startTime = $this->item->scheduled_start_time->getTimestamp();
            $this->endTime = $this->item->scheduled_end_time->getTimestamp();
        }

        $this->currentTime = time();

        $this->isActive = $this->item->is_in_effect
            || $this->item->is_flexible && $this->item->scheduled_start_time->getTimestamp() <= $this->currentTime;

        $until = ($this->isActive ? $this->endTime : $this->startTime) - $this->currentTime;
        $this->duration = explode(' ', DateFormatter::formatDuration(
            $until <= 3600 ? $until : $until + (3600 - ((int) $until % 3600))
        ), 2)[0];

        $this->list->addDetailFilterAttribute($this, Filter::equal('name', $this->item->name));
        $this->list->addMultiselectFilterAttribute($this, Filter::equal('name', $this->item->name));
        $this->setObjectLinkDisabled($this->list->getObjectLinkDisabled());
        $this->setNoSubjectLink($this->list->getNoSubjectLink());
        $this->setTicketLinkEnabled($this->list->getTicketLinkEnabled());

        if ($this->item->is_in_effect) {
            $this->getAttributes()->add('class', 'in-effect');
        }
    }

    protected function createProgress(): BaseHtmlElement
    {
        return new HtmlElement(
            'div',
            Attributes::create([
                'class' => 'progress',
                'data-animate-progress' => true,
                'data-start-time' => $this->startTime,
                'data-end-time' => $this->endTime
            ]),
            new HtmlElement(
                'div',
                Attributes::create(['class' => 'bar'])
            )
        );
    }

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $markdownLine = new MarkdownLine($this->createTicketLinks($this->item->comment));
        $caption->getAttributes()->add($markdownLine->getAttributes());
        $caption->addHtml(
            new HtmlElement(
                'span',
                null,
                new Icon(Icons::USER),
                Text::create($this->item->author)
            ),
            Text::create(': ')
        )->addFrom($markdownLine);
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        if ($this->getObjectLinkDisabled()) {
            $link = null;
        } elseif ($this->item->object_type === 'host') {
            $link = $this->createHostLink($this->item->host, true);
        } else {
            $link = $this->createServiceLink($this->item->service, $this->item->service->host, true);
        }

        if ($this->item->is_flexible) {
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

        if ($this->getNoSubjectLink()) {
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
                $title->addHtml(new Link($template, Links::downtime($this->item)));
            } else {
                $title->addHtml(TemplateString::create(
                    $template,
                    ['link' => new Link('', Links::downtime($this->item))],
                    $link
                ));
            }
        }
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
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

    protected function createTimestamp(): ?BaseHtmlElement
    {
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
