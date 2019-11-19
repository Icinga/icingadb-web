<?php

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Model\State;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;

/**
 * Host or service item of a host or service list. Represents one database row.
 */
abstract class StateListItem extends BaseListItem
{
    /** @var State The state of the item */
    protected $state;

    protected function init()
    {
        $this->state = $this->item->state;
    }

    abstract protected function createSubject();

    abstract protected function getStateBallSize();

    protected function assembleCaption(BaseHtmlElement $caption)
    {
        $caption
            ->add($this->state->output)
            ->getAttributes()
                ->add('class', 'plugin-output');

    }

    protected function assembleTitle(BaseHtmlElement $title)
    {
        $title->add([
            $this->createSubject(),
            ' is ',
            Html::tag('span', ['class' => 'state-text'], $this->state->getStateTextTranslated())
        ]);
    }

    protected function assembleVisual(BaseHtmlElement $visual)
    {
        $stateBall = new StateBall($this->state->getStateText(), $this->getStateBallSize());

        if ($this->state->is_handled) {
            switch (true) {
                case $this->state->in_downtime:
                    $icon = Icons::IN_DOWNTIME;
                    break;
                case $this->state->is_acknowledged:
                    $icon = Icons::IS_ACKNOWLEDGED;
                    break;
                case $this->state->is_flapping:
                    $icon = Icons::IS_FLAPPING;
                    break;
                default:
                    $icon = Icons::HOST_DOWN;
            }

            $stateBall->add(new Icon($icon));
            $stateBall->getAttributes()->add('class', 'handled');
        }

        $visual->add($stateBall);
        if ($this->state->state_type === 'soft') {
            $visual->add(new CheckAttempt($this->state->attempt, $this->item->max_check_attempts));
        }
    }

    protected function createTimestamp()
    {
        if ($this->state->is_overdue) {
            $since = new TimeSince($this->state->next_check);
            $since->prepend('Overdue ');
            $since->prepend(new Icon(Icons::WARNING));
        } else {
            $since = new TimeSince($this->state->last_update);
        }

        return $since;
    }

    protected function assemble()
    {
        if ($this->state->is_overdue) {
            $this->addAttributes(['class' => 'overdue']);
        }

        parent::assemble();
    }
}
