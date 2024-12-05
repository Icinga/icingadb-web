<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Common\TicketLinks;
use Icinga\Module\Icingadb\Model\Comment;
use Icinga\Module\Icingadb\Widget\MarkdownLine;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\FormattedString;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\TimeUntil;

trait CommentHeaderUtils
{
    use TicketLinks;
    use HostLink;
    use ServiceLink;
    use Translation;

    /**
     * Get the object
     *
     * @return Comment
     */
    abstract protected function getObject(): Comment;

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

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new HtmlElement(
            'div',
            Attributes::create(['class' => 'user-ball']),
            Text::create($this->getObject()->author[0])
        ));
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $comment = $this->getObject();
        $isAck = $comment->entry_type === 'ack';
        $expires = $comment->expire_time;

        $subjectText = sprintf(
            $isAck ? t('%s acknowledged', '<username>..') : t('%s commented', '<username>..'),
            $comment->author
        );

        $headerParts = [
            new Icon(Icons::USER),
            $this->wantSubjectLink()
                ? new Link($subjectText, Links::comment($comment), ['class' => 'subject'])
                : new HtmlElement('span', Attributes::create(['class' => 'subject']), Text::create($subjectText))

        ];

        if ($isAck) {
            $label = [Text::create('ack')];

            if ($comment->is_persistent) {
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
                Text::create(t('EXPIRES'))
            );
        }

        if ($this->wantObjectLink()) {
            $headerParts[] = $comment->object_type === 'host'
                ? $this->createHostLink($comment->host, true)
                : $this->createServiceLink($comment->service, $comment->service->host, true);
        }

        $title->addHtml(...$headerParts);
    }

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $markdownLine = new MarkdownLine($this->createTicketLinks($this->getObject()->text));

        $caption->getAttributes()->add($markdownLine->getAttributes());
        $caption->addFrom($markdownLine);
    }


    protected function createTimestamp(): BaseHtmlElement
    {
        $comment = $this->getObject();
        if ($comment->expire_time) {
            return Html::tag(
                'span',
                FormattedString::create(
                    $this->translate("expires %s"),
                    new TimeUntil($comment->expire_time->getTimestamp())
                )
            );
        }

        return Html::tag(
            'span',
            FormattedString::create(
                $this->translate("created %s"),
                new TimeAgo($comment->entry_time->getTimestamp())
            )
        );
    }
}
