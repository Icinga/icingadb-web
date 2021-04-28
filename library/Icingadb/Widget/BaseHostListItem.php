<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Model\Host;
use ipl\Html\HtmlElement;
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
    use NoSubjectLink;

    protected function createSubject()
    {
        if ($this->getNoSubjectLink()) {
            return new HtmlElement('span', ['class' => 'subject'], $this->item->display_name);
        } else {
            return new Link($this->item->display_name, Links::host($this->item), ['class' => 'subject']);
        }
    }

    protected function init()
    {
        parent::init();

        $this->setMultiselectFilter(Filter::equal('host.name', $this->item->name));
        $this->setDetailFilter(Filter::equal('name', $this->item->name));

        if ($this->list->getNoSubjectLink()) {
            $this->setNoSubjectLink();
        }
    }
}
