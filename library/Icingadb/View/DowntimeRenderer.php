<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\View;

use Icinga\Date\DateFormatter;
use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Common\TicketLinks;
use Icinga\Module\Icingadb\Model\Downtime;
use Icinga\Module\Icingadb\Widget\MarkdownLine;
use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\TemplateString;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Common\ItemRenderer;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

/** @implements ItemRenderer<Downtime> */
class DowntimeRenderer implements ItemRenderer
{
    use Translation;
    use TicketLinks;
    use HostLink;
    use ServiceLink;

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

    /** @var bool Whether the state has been loaded */
    protected $stateLoaded = false;

    /** @var bool Whether the object link for th item should be omitted */
    protected $noObjectLink = false;

    /**
     * Set whether the object link for th item should be omitted
     *
     * @param bool $state
     *
     * @return $this
     */
    public function setNoObjectLink(bool $state = true): self
    {
        $this->noObjectLink = $state;

        return $this;
    }

    /**
     * Load the state of the downtime
     *
     * @param Downtime $item
     *
     * @return void
     */
    protected function loadState(Downtime $item): void
    {
        if ($this->stateLoaded) {
            return;
        }

        if (
            isset($item->start_time, $item->end_time)
            && $item->is_flexible
            && $item->is_in_effect
        ) {
            $this->startTime = $item->start_time->getTimestamp();
            $this->endTime = $item->end_time->getTimestamp();
        } else {
            $this->startTime = $item->scheduled_start_time->getTimestamp();
            $this->endTime = $item->scheduled_end_time->getTimestamp();
        }

        $this->currentTime = time();

        $this->isActive = $item->is_in_effect
            || ($item->is_flexible && $item->scheduled_start_time->getTimestamp() <= $this->currentTime);

        $until = ($this->isActive ? $this->endTime : $this->startTime) - $this->currentTime;
        $this->duration = explode(' ', DateFormatter::formatDuration(
            $until <= 3600 ? $until : $until + (3600 - ((int) $until % 3600))
        ), 2)[0];

        $this->stateLoaded = true;
    }

    public function assembleAttributes($item, Attributes $attributes, string $layout): void
    {
        $attributes->add(new Attributes(['class' => ['downtime', $item->is_in_effect ? 'in-effect' : '']]));
    }

    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        $this->loadState($item);

        $dateTime = DateFormatter::formatDateTime($this->endTime);

        if ($this->isActive) {
            $visual->addHtml(Html::sprintf(
                $this->translate('%s left', '<timespan>..'),
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
                $this->translate('in %s', '..<timespan>'),
                Html::tag('strong', $this->duration)
            ));
        }
    }

    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        if ($this->noObjectLink) {
            $link = null;
        } elseif ($item->object_type === 'host') {
            $link = $this->createHostLink($item->host, true);
        } else {
            $link = $this->createServiceLink($item->service, $item->service->host, true);
        }

        if ($item->is_flexible) {
            if ($link !== null) {
                $template = $this->translate('{{#link}}Flexible Downtime{{/link}} for %s');
            } else {
                $template = $this->translate('Flexible Downtime');
            }
        } else {
            if ($link !== null) {
                $template = $this->translate('{{#link}}Fixed Downtime{{/link}} for %s');
            } else {
                $template = $this->translate('Fixed Downtime');
            }
        }

        if ($layout === 'header') {
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
                $title->addHtml(new Link($template, Links::downtime($item)));
            } else {
                $title->addHtml(TemplateString::create(
                    $template,
                    ['link' => new Link('', Links::downtime($item))],
                    $link
                ));
            }
        }
    }

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
        $markdownLine = new MarkdownLine($this->createTicketLinks($item->comment));
        $caption->getAttributes()->add($markdownLine->getAttributes());
        $caption->addHtml(
            new HtmlElement(
                'span',
                null,
                new Icon(Icons::USER),
                Text::create($item->author)
            ),
            Text::create(': ')
        )->addFrom($markdownLine);
    }

    public function assembleExtendedInfo($item, HtmlDocument $info, string $layout): void
    {
        $this->loadState($item);

        $dateTime = DateFormatter::formatDateTime($this->isActive ? $this->endTime : $this->startTime);

        $info->addHtml(Html::tag(
            'time',
            [
                'datetime' => $dateTime,
                'title'    => $dateTime
            ],
            sprintf(
                $this->isActive
                    ? $this->translate('expires in %s', '..<timespan>')
                    : $this->translate('starts in %s', '..<timespan>'),
                $this->duration
            )
        ));
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
    }

    public function assemble($item, string $name, HtmlDocument $element, string $layout): bool
    {
        if ($name === 'progress' && ($layout === 'detailed' || $layout === 'common')) {
            $this->loadState($item);

            $element
                ->addAttributes(Attributes::create([
                    'data-animate-progress' => true,
                    'data-start-time' => $this->startTime,
                    'data-end-time' => $this->endTime
                ]))
                ->addHtml(new HtmlElement('div', Attributes::create(['class' => 'bar'])));

            return true;
        }

        return false;
    }
}
