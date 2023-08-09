<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Web\Common\BaseOrderedListItem;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

class CommandTransportListItem extends BaseOrderedListItem
{
    protected function init(): void
    {
        $this->list->addDetailFilterAttribute($this, Filter::equal('name', $this->item->name));
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml(new Link(
            new HtmlElement('strong', null, Text::create($this->item->name)),
            Url::fromPath('icingadb/command-transport/show', ['name' => $this->item->name])
        ));

        $main->addHtml(new Link(
            new Icon('trash', ['title' => sprintf(t('Remove command transport "%s"'), $this->item->name)]),
            Url::fromPath('icingadb/command-transport/remove', ['name' => $this->item->name]),
            [
                'class' => 'pull-right action-link',
                'data-icinga-modal' => true,
                'data-no-icinga-ajax' => true
            ]
        ));

        if ($this->getOrder() + 1 < $this->list->count()) {
            $main->addHtml((new Link(
                new Icon('arrow-down'),
                Url::fromPath('icingadb/command-transport/sort', [
                    'name'  => $this->item->name,
                    'pos'   => $this->getOrder() + 1
                ]),
                ['class' => 'pull-right action-link']
            ))->setBaseTarget('_self'));
        }

        if ($this->getOrder() > 0) {
            $main->addHtml((new Link(
                new Icon('arrow-up'),
                Url::fromPath('icingadb/command-transport/sort', [
                    'name'  => $this->item->name,
                    'pos'   => $this->getOrder() - 1
                ]),
                ['class' => 'pull-right action-link']
            ))->setBaseTarget('_self'));
        }
    }

    protected function createVisual(): ?BaseHtmlElement
    {
        return null;
    }
}
