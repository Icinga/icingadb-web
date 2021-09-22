<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\HostStates;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\StateBall;

class HostDetailHeader extends HostListItemMinimal
{
    protected function getStateBallSize(): string
    {
        if ($this->state->state_type === 'soft') {
            return StateBall::SIZE_MEDIUM_LARGE;
        }

        return StateBall::SIZE_BIG;
    }

    protected function assembleVisual(BaseHtmlElement $visual)
    {
        parent::assembleVisual($visual);

        $isSoftState = false;
        if ($this->state->state_type === 'soft') {
            $isSoftState = true;
        }

        // When the current state type is a soft state change, then use the actual hard_state and not the prev. ones
        $previousState = $isSoftState ? $this->state->hard_state : $this->state->previous_hard_state;
        $previousHardState = HostStates::text($previousState);

        $visual->getFirst('span')->addWrapper(new HtmlElement(
            'div',
            Attributes::create(['class' => 'state-change']),
            new StateBall($previousHardState, StateBall::SIZE_BIG)
        ));
    }

    protected function assemble()
    {
        $attributes = $this->list->getAttributes();
        if (! in_array('minimal', $attributes->get('class')->getValue())) {
            $attributes->add('class', 'minimal');
        }

        parent::assemble();
    }
}
