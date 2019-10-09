<?php

namespace Icinga\Module\Eagle\Widget;

use ipl\Html\Html;
use ipl\Web\Url;

/**
 * Service item of a service list. Represents one database row.
 */
abstract class BaseServiceListItem extends StateListItem
{
    protected function createSubject()
    {
        return Html::tag('a', [
            'href' => Url::fromPath('eagle/service', [
                'name'      => $this->item->name,
                'host_name' => $this->item->host->name
            ]),
            'class' => 'subject'
        ], $this->item->display_name);
    }
}
