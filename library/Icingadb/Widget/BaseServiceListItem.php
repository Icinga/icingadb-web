<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Data\Filter\FilterAnd;
use Icinga\Data\Filter\FilterExpression;
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
        return [Html::sprintf(
            t('%s on %s', '<service> on <host>'),
            Html::tag(
                'a',
                [
                    'href'  => Links::service($this->item, $this->item->host),
                    'class' => 'subject'
                ],
                $this->item->display_name
            ),
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
        )];
    }

    protected function init()
    {
        parent::init();

        $this->setMultiselectFilter(
            (new FilterAnd())
                ->addFilter(new FilterExpression('service.name', '=', $this->item->name))
                ->addFilter(new FilterExpression('host.name', '=', $this->item->host->name))
        );
        $this->setDetailFilter(
            (new FilterAnd())
                ->addFilter(new FilterExpression('name', '=', $this->item->name))
                ->addFilter(new FilterExpression('host.name', '=', $this->item->host->name))
        );
    }
}
