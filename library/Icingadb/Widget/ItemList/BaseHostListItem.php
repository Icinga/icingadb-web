<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Host;
use ipl\Html\ValidHtml;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Link;

/**
 * Host item of a host list. Represents one database row.
 *
 * @property Host $item
 * @property HostList $list
 */
abstract class BaseHostListItem extends StateListItem
{
    /**
     * Create new subject link
     *
     * @return Link
     */
    protected function createSubject(): ValidHtml
    {
        return new Link($this->item->display_name, Links::host($this->item), ['class' => 'subject']);
    }

    protected function init(): void
    {
        parent::init();

        $this->list->addDetailFilterAttribute($this, Filter::equal('name', $this->item->name))
            ->addMultiselectFilterAttribute($this, Filter::equal('host.name', $this->item->name));
    }
}
