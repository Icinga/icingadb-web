<?php

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Data\Filter\FilterExpression;
use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Widget\BaseListItem;
use Icinga\Module\Icingadb\Widget\TimeAgo;
use Icinga\Web\Helper\Markdown;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

abstract class BaseCommentListItem extends BaseListItem
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
        $expires = $this->item->expire_time;

        $header->add([
            new Icon(Icons::USER),
            new Link(
                [
                    $this->item->author,
                    ' ',
                    ($isAck ? 'acknowledged' : 'commented')
                ],
                Links::comment($this->item)
            )
        ]);

        if ($isAck) {
            $label = ['ack'];

            if ($this->item->is_persistent) {
                array_unshift($label, new Icon(Icons::IS_PERSISTENT));
            }

            $header->add(HTML::tag('span', ['class' => 'ack-badge badge'], $label));
        }

        if ($expires != 0) {
            $header->add(HTML::tag('span', ['class' => 'ack-badge badge'], 'EXPIRES'));
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
        return new TimeAgo($this->item->entry_time);
    }

    protected function init()
    {
        $this->setMultiselectFilter(new FilterExpression('name', '=', $this->item->name));
    }
}
