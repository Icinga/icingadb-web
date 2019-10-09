<?php

namespace Icinga\Module\Eagle\Widget;

use ipl\Html\Html;
use ipl\Web\Url;

/**
 * Host item of a host list. Represents one database row.
 */
abstract class BaseHostListItem extends StateListItem
{
    protected function createSubject()
    {
        return Html::tag('a', [
            'href'  => Url::fromPath('eagle/host', ['name' => $this->item->name]),
            'class' => 'subject'
        ], $this->item->display_name);
    }
}
