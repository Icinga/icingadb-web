<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Model\Downtime;
use Icinga\Module\Icingadb\Widget\Detail\DowntimeHeaderUtils;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Stdlib\Filter;
use ipl\Web\Common\BaseListItem;

/**
 * Downtime item of a downtime list. Represents one database row.
 *
 * @property Downtime $item
 * @property DowntimeList $list
 */
abstract class BaseDowntimeListItem extends BaseListItem
{
    use DowntimeHeaderUtils;

    protected function getObject(): Downtime
    {
        return $this->item;
    }

    protected function wantSubjectLink(): bool
    {
        return ! $this->list->getNoSubjectLink();
    }

    protected function wantObjectLink(): bool
    {
        return ! $this->list->getObjectLinkDisabled();
    }

    protected function init(): void
    {
        $this->list->addDetailFilterAttribute($this, Filter::equal('name', $this->item->name));
        $this->list->addMultiselectFilterAttribute($this, Filter::equal('name', $this->item->name));
        $this->setTicketLinkEnabled($this->list->getTicketLinkEnabled());
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
}
