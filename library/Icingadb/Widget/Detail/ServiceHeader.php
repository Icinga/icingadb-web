<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\ServiceStates;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Model\ServiceState;
use Icinga\Module\Icingadb\Widget\StateChange;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;

/**
 * @property Service $object
 * @property ServiceState $state
 */
class ServiceHeader extends BaseHostAndServiceHeader
{
    protected $defaultAttributes = ['class' => 'service-header'];
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

        $stateChange->setIcon($this->state->getIcon());
        $stateChange->setHandled(
            $this->state->is_problem && ($this->state->is_handled || ! $this->state->is_reachable)
        );

        $visual->addHtml($stateChange);
    }

    protected function createSubject(): ValidHtml
    {
        $service = $this->object->display_name;
        $host = [
            new StateBall($this->object->host->state->getStateText(), StateBall::SIZE_MEDIUM),
            ' ',
            $this->object->host->display_name
        ];

        $host = new Link($host, Links::host($this->object->host), ['class' => 'subject']);
        $service = new HtmlElement('span', Attributes::create(['class' => 'subject']), Text::create($service));

        return Html::sprintf(t('%s on %s', '<service> on <host>'), $service, $host);
    }
}
