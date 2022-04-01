<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\ServiceStates;
use Icinga\Module\Icingadb\Widget\CheckAttempt;
use Icinga\Module\Icingadb\Widget\StateChange;
use ipl\Html\BaseHtmlElement;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;

class ServiceDetailHeader extends ServiceListItemMinimal
{
    protected function getStateBallSize(): string
    {
        return '';
    }

    protected function assembleVisual(BaseHtmlElement $visual)
    {
        if ($this->state->state_type === 'soft') {
            $stateType = 'soft_state';
            $previousStateType = 'previous_soft_state';

            if ($this->state->previous_soft_state === 0) {
                $previousStateType = 'hard_state';
            }
        } else {
            $stateType = 'hard_state';
            $previousStateType = 'previous_hard_state';

            if ($this->state->hard_state === $this->state->previous_hard_state) {
                $previousStateType = 'previous_soft_state';
            }
        }

        $state = ServiceStates::text($this->state->$stateType);
        $previousState = ServiceStates::text($this->state->$previousStateType);

        $stateChange = new StateChange($state, $previousState);
        if ($stateType === 'soft_state') {
            $stateChange->setCurrentStateBallSize(StateBall::SIZE_MEDIUM_LARGE);
        }

        if ($previousStateType === 'previous_soft_state') {
            $stateChange->setPreviousStateBallSize(StateBall::SIZE_MEDIUM_LARGE);
            if ($stateType === 'soft_state') {
                $visual->getAttributes()->add('class', 'small-state-change');
            }
        }

        if ($this->state->is_handled) {
            $currentStateBall = $stateChange->ensureAssembled()->getContent()[1];
            $currentStateBall->addHtml(new Icon($this->getHandledIcon()));
            $currentStateBall->getAttributes()->add('class', 'handled');
        }

        $visual->addHtml($stateChange);
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
