<?php

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\Links;
use ipl\Html\Html;
use ipl\Web\Widget\StateBall;

/**
 * Service item of a service list. Represents one database row.
 */
abstract class BaseServiceListItem extends StateListItem
{
    protected function createSubject()
    {
        return [
            Html::tag(
                'a',
                [
                    'href'  => Links::service($this->item, $this->item->host),
                    'class' => 'subject'
                ],
                $this->item->display_name
            ),
            ' on ',
            Html::tag(
                'a',
                [
                    'href'  => Links::host($this->item->host),
                    'class' => 'subject'
                ],
                [
                    new StateBall($this->item->host->state->getStateText(), StateBall::SIZE_MEDIUM),
                    ' ',
                    $this->item->host->display_name
                ]
            )
        ];
    }
}
