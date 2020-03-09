<?php

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Data\Filter\FilterExpression;
use Icinga\Module\Icingadb\Common\Links;
use ipl\Html\Html;

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

        $this->setMultiselectFilter(new FilterExpression('host.name', '=', $this->item->name));
        $this->setDetailFilter(new FilterExpression('name', '=', $this->item->name));
    }
}
