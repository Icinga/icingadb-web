<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\HostStates;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\HostState;
use Icinga\Module\Icingadb\Widget\StateChange;
use ipl\Html\BaseHtmlElement;
use ipl\Web\Widget\StateBall;

/**
 * @property Host $object
 * @property HostState $state
 */
class HostHeader extends BaseHostAndServiceHeader
{
    protected $defaultAttributes = ['class' => 'host-header'];

    protected function assembleVisual(BaseHtmlElement $visual): void
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

        $state = HostStates::text($this->state->$stateType);
        $previousState = HostStates::text($this->state->$previousStateType);

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

        $stateChange->setIcon($this->state->getIcon());
        $stateChange->setHandled(
            $this->state->is_problem && ($this->state->is_handled || ! $this->state->is_reachable)
        );

        $visual->addHtml($stateChange);
    }
}
