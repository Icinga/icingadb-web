<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\BaseListItem;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Model\State;
use Icinga\Module\Icingadb\Util\PluginOutput;
use Icinga\Module\Icingadb\Widget\CheckAttempt;
use Icinga\Module\Icingadb\Widget\EmptyState;
use Icinga\Module\Icingadb\Widget\IconImage;
use Icinga\Module\Icingadb\Widget\PluginOutputContainer;
use ipl\Web\Widget\TimeSince;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\Text;
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

        if (isset($this->item->icon_image->icon_image)) {
            $this->list->setHasIconImages(true);
        }
    }

    abstract protected function createSubject();

    abstract protected function getStateBallSize(): string;

    protected function assembleCaption(BaseHtmlElement $caption)
    {
        if ($this->state->soft_state === null && $this->state->output === null) {
            $caption->addHtml(Text::create(t('Waiting for Icinga DB to synchronize the state.')));
        } else {
            if (empty($this->state->output)) {
                $pluginOutput = new EmptyState(t('Output unavailable.'));
            } else {
                $pluginOutput = new PluginOutputContainer(PluginOutput::fromObject($this->item));
            }

            $caption->addHtml($pluginOutput);
        }
    }

    protected function assembleIconImage(BaseHtmlElement $iconImage)
    {
        if (isset($this->item->icon_image->icon_image)) {
            $iconImage->addHtml(new IconImage($this->item->icon_image->icon_image, $this->item->icon_image_alt));
        } else {
            $iconImage->addAttributes(['class' => 'placeholder']);
        }
    }

    protected function assembleTitle(BaseHtmlElement $title)
    {
        $title->addHtml(Html::sprintf(
            t('%s is %s', '<hostname> is <state-text>'),
            $this->createSubject(),
            Html::tag('span', ['class' => 'state-text'], $this->state->getStateTextTranslated())
        ));
    }

    protected function assembleVisual(BaseHtmlElement $visual)
    {
        $stateBall = new StateBall($this->state->getStateText(), $this->getStateBallSize());

        if ($this->state->is_handled) {
            $stateBall->addHtml(new Icon($this->getHandledIcon()));
            $stateBall->getAttributes()->add('class', 'handled');
        } elseif ($this->state->getStateText() === 'pending' && $this->state->in_downtime) {
            $stateBall->addHtml(new Icon($this->getHandledIcon()));
        }

        $visual->addHtml($stateBall);
        if ($this->state->state_type === 'soft') {
            $visual->addHtml(
                new CheckAttempt((int) $this->state->check_attempt, (int) $this->item->max_check_attempts)
            );
        }
    }

    protected function createTimestamp()
    {
        if ($this->state->is_overdue) {
            $since = new TimeSince($this->state->next_update);
            $since->prepend(t('Overdue') . ' ');
            $since->prependHtml(new Icon(Icons::WARNING));
            return $since;
        } elseif ($this->state->last_state_change > 0) {
            return new TimeSince($this->state->last_state_change);
        }
    }

    protected function getHandledIcon(): string
    {
        switch (true) {
            case $this->state->is_acknowledged:
                return Icons::IS_ACKNOWLEDGED;
            case $this->state->in_downtime:
                return Icons::IN_DOWNTIME;
            case $this->state->is_flapping:
                return Icons::IS_FLAPPING;
            default:
                return Icons::HOST_DOWN;
        }
    }

    protected function assemble()
    {
        if ($this->state->is_overdue) {
            $this->addAttributes(['class' => 'overdue']);
        }

        parent::assemble();
    }
}
