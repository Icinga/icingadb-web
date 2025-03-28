<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\View;

use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Common\TicketLinks;
use Icinga\Module\Icingadb\Model\Comment;
use Icinga\Module\Icingadb\Widget\MarkdownLine;
use ipl\Html\Attributes;
use ipl\Html\FormattedString;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Common\ItemRenderer;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\TimeUntil;

/** @implements ItemRenderer<Comment> */
class CommentRenderer implements ItemRenderer
{
    use Translation;
    use TicketLinks;
    use HostLink;
    use ServiceLink;

    /** @var bool Whether the object link for th item should be omitted */
    protected $noObjectLink = false;

    /** @var bool Whether item's subject should be a link */
    protected $noSubjectLink = false;

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
     * Set whether item's subject should be a link
     *
     * @param bool $state
     *
     * @return $this
     */
    public function setNoSubjectLink(bool $state = true): self
    {
        $this->noSubjectLink = $state;

        return $this;
    }

    public function assembleAttributes($item, Attributes $attributes, string $layout): void
    {
        $attributes->get('class')->addValue('comment');
    }

    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        $visual->addHtml(new HtmlElement(
            'div',
            Attributes::create(['class' => 'user-ball']),
            Text::create($item->author[0])
        ));
    }

    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        $isAck = $item->entry_type === 'ack';
        $expires = $item->expire_time;

        $subjectText = sprintf(
            $isAck
                ? $this->translate('%s acknowledged', '<username>..')
                : $this->translate('%s commented', '<username>..'),
            $item->author
        );

        $headerParts = [
            new Icon(Icons::USER),
            $layout === 'header' || $this->noSubjectLink
                ? new HtmlElement('span', Attributes::create(['class' => 'subject']), Text::create($subjectText))
                : new Link($subjectText, Links::comment($item), ['class' => 'subject'])
        ];

        if ($isAck) {
            $label = [Text::create('ack')];

            if ($item->is_persistent) {
                array_unshift($label, new Icon(Icons::IS_PERSISTENT));
            }

            $headerParts[] = Text::create(' ');
            $headerParts[] = new HtmlElement('span', Attributes::create(['class' => 'ack-badge badge']), ...$label);
        }

        if ($expires !== null) {
            $headerParts[] = Text::create(' ');
            $headerParts[] = new HtmlElement(
                'span',
                Attributes::create(['class' => 'ack-badge badge']),
                Text::create($this->translate('EXPIRES'))
            );
        }

        if ($this->noObjectLink) {
            // pass
        } elseif ($item->object_type === 'host') {
            $headerParts[] = $this->createHostLink($item->host, true);
        } else {
            $headerParts[] = $this->createServiceLink($item->service, $item->service->host, true);
        }

        $title->addHtml(...$headerParts);
    }

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
        $markdownLine = new MarkdownLine($this->createTicketLinks($item->text));

        $caption->getAttributes()->add($markdownLine->getAttributes());
        $caption->addFrom($markdownLine);
    }

    public function assembleExtendedInfo($item, HtmlDocument $info, string $layout): void
    {
        if ($item->expire_time) {
            $info->addHtml(
                FormattedString::create(
                    $this->translate("expires %s"),
                    new TimeUntil($item->expire_time->getTimestamp())
                )
            );
        } else {
            $info->addHtml(
                FormattedString::create(
                    $this->translate("created %s"),
                    new TimeAgo($item->entry_time->getTimestamp())
                )
            );
        }
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
    }

    public function assemble($item, string $name, HtmlDocument $element, string $layout): bool
    {
        return false; // no custom sections
    }
}
