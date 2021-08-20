<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Widget\MarkdownLine;
use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Common\ObjectLinkDisabled;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Model\Comment;
use Icinga\Module\Icingadb\Common\BaseListItem;
use ipl\Web\Widget\TimeAgo;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

/**
 * Comment item of a comment list. Represents one database row.
 *
 * @property Comment $item
 * @property CommentList $list
 */
abstract class BaseCommentListItem extends BaseListItem
{
    use HostLink;
    use ServiceLink;
    use NoSubjectLink;
    use ObjectLinkDisabled;

    protected function assembleCaption(BaseHtmlElement $caption)
    {
        $markdownLine = new MarkdownLine($this->item->text);
        $caption->getAttributes()->add($markdownLine->getAttributes());
        $caption->addFrom($markdownLine);
    }

    protected function assembleTitle(BaseHtmlElement $title)
    {
        $isAck = $this->item->entry_type === 'ack';
        $expires = $this->item->expire_time;

        $subjectText = sprintf(
            $isAck ? t('%s acknowledged', '<username>..') : t('%s commented', '<username>..'),
            $this->item->author
        );

        $headerParts = [
            new Icon(Icons::USER),
            $this->getNoSubjectLink()
                ? new HtmlElement('span', Attributes::create(['class' => 'subject']), Text::create($subjectText))
                : new Link($subjectText, Links::comment($this->item), ['class' => 'subject'])
        ];

        if ($isAck) {
            $label = [Text::create('ack')];

            if ($this->item->is_persistent) {
                array_unshift($label, new Icon(Icons::IS_PERSISTENT));
            }

            $headerParts[] = Text::create(' ');
            $headerParts[] = new HtmlElement('span', Attributes::create(['class' => 'ack-badge badge']), ...$label);
        }

        if ($expires != 0) {
            $headerParts[] = Text::create(' ');
            $headerParts[] = new HtmlElement(
                'span',
                Attributes::create(['class' => 'ack-badge badge']),
                Text::create(t('EXPIRES'))
            );
        }

        if ($this->getObjectLinkDisabled()) {
            // pass
        } elseif ($this->item->object_type === 'host') {
            $headerParts[] = $this->createHostLink($this->item->host, true);
        } else {
            $headerParts[] = $this->createServiceLink($this->item->service, $this->item->service->host, true);
        }

        $title->addHtml(...$headerParts);
    }

    protected function assembleVisual(BaseHtmlElement $visual)
    {
        $visual->addHtml(new HtmlElement(
            'div',
            Attributes::create(['class' => 'user-ball']),
            Text::create($this->item->author[0])
        ));
    }

    protected function createTimestamp()
    {
        return new TimeAgo($this->item->entry_time);
    }

    protected function init()
    {
        $this->setMultiselectFilter(Filter::equal('name', $this->item->name));
        $this->setDetailFilter(Filter::equal('name', $this->item->name));
        $this->setObjectLinkDisabled($this->list->getObjectLinkDisabled());
        $this->setNoSubjectLink($this->list->getNoSubjectLink());
    }
}
