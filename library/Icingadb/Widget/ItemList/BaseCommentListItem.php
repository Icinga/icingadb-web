<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

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

        $headerLineOne = Html::tag('p');

        $headerLineOne->add([
            new Icon(Icons::USER),
            new Link(
                sprintf(
                    $isAck ? t('%s acknowledged', '<username>..') : t('%s commented', '<username>..'),
                    $this->item->author
                ),
                Links::comment($this->item)
            )
        ]);

        if ($isAck) {
            $label = ['ack'];

            if ($this->item->is_persistent) {
                array_unshift($label, new Icon(Icons::IS_PERSISTENT));
            }

            $headerLineOne->add([' ', HTML::tag('span', ['class' => 'ack-badge badge'], $label)]);
        }

        if ($expires != 0) {
            $headerLineOne->add([' ', HTML::tag('span', ['class' => 'ack-badge badge'], t('EXPIRES'))]);
        }

        $header->add($headerLineOne);

        if ($this->item->object_type === 'host') {
            $link = $this->createHostLink($this->item->host, true);
        } else {
            $link = $this->createServiceLink($this->item->service, $this->item->service->host, true);
        }

        $header->add(Html::tag('p', $link));
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
        $this->setDetailFilter(new FilterExpression('name', '=', $this->item->name));
    }
}
