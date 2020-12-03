<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\Links;
use ipl\Html\Html;
use ipl\Stdlib\Filter;

/**
 * Host item of a host list. Represents one database row.
 */
abstract class BaseHostListItem extends StateListItem
{
    protected function createSubject()
    {
        return Html::tag('a', [
            'href'  => Links::host($this->item),
            'class' => 'subject'
        ], $this->item->display_name);
    }

    protected function init()
    {
        parent::init();

        $this->setMultiselectFilter(Filter::equal('host.name', $this->item->name));
        $this->setDetailFilter(Filter::equal('name', $this->item->name));
    }
}
