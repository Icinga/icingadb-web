<?php

namespace Icinga\Module\Eagle\Widget;

use Icinga\Module\Eagle\Common\Icons;
use Icinga\Module\Eagle\Model\State;
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
        $visual->add([
            new StateBall($this->state->getStateText(), StateBall::SIZE_LARGE),
            new CheckAttempt($this->state->attempt, $this->item->max_check_attempts)
        ]);
    }

    protected function createTimestamp()
    {
        $since = new TimeSince($this->state->last_update);

        if ($this->state->is_overdue) {
            $since->prepend('Overdue ');
            $since->prepend(new Icon(Icons::WARNING));
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
