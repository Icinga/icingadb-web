<?php

namespace Icinga\Module\Eagle\Widget;

use Icinga\Module\Eagle\Common\Links;
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
}
