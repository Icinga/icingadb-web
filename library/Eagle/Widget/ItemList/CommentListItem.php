<?php


namespace Icinga\Module\Eagle\Widget\ItemList;

use Icinga\Date\DateFormatter;
use Icinga\Module\Eagle\Common\HostLink;
use Icinga\Module\Eagle\Common\Icons;
use Icinga\Module\Eagle\Common\ServiceLink;
use Icinga\Module\Eagle\Widget\CommonListItem;
use Icinga\Web\Helper\Markdown;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use ipl\Web\Widget\Icon;

class CommentListItem extends CommonListItem
{
    use HostLink;
    use ServiceLink;

    protected function assembleCaption(BaseHtmlElement $caption)
    {
        $caption->add(new HtmlString(Markdown::line($this->item->text)));
    }

    protected function assembleTitle(BaseHtmlElement $header)
    {
        $isAck = $this->item->entry_type === 'ack';

        $header->add([
            new Icon(Icons::USER),
            $this->item->author,
            ' ',
            ($isAck ? 'acknowledged' : 'commented'),
        ]);

        if ($isAck) {
            $label = ['ack'];

            if ($this->item->is_persistent) {
                array_unshift($label, new Icon(Icons::IS_PERSISTENT));
            }

            $header->add(HTML::tag('span', ['class' => 'ack-badge badge'], $label));
        }

        $header->add(Html::tag('br'));

        if ($this->item->object_type === 'host') {
            $link = $this->createHostLink($this->item->host, true);
        } else {
            $link = $this->createServiceLink($this->item->service, $this->item->service->host, true);
        }

        $header->add($link);
    }

    protected function assembleVisual(BaseHtmlElement $visual)
    {
        $visual->add(
            Html::tag('div', ['class' => 'user-ball'], $this->item->author[0])
        );
    }

    protected function createTimestamp()
    {
        return DateFormatter::timeAgo($this->item->entry_time);
    }
}
