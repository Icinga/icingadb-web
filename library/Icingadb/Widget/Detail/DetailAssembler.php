<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Util\PluginOutput;
use Icinga\Module\Icingadb\Widget\CheckAttempt;
use Icinga\Module\Icingadb\Widget\IconImage;
use Icinga\Module\Icingadb\Widget\PluginOutputContainer;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeSince;

trait DetailAssembler
{
    /**
     * Create subject
     *
     * @return BaseHtmlElement
     */
    abstract protected function createSubject();

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        if ($this->state->soft_state === null && $this->state->output === null) {
            $caption->addHtml(Text::create($this->translate('Waiting for Icinga DB to synchronize the state.')));
        } else {
            if (empty($this->state->output)) {
                $pluginOutput = new EmptyState($this->translate('Output unavailable.'));
            } else {
                $pluginOutput = new PluginOutputContainer(PluginOutput::fromObject($this->item));
            }

            $caption->addHtml($pluginOutput);
        }
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<hostname> is <state-text>'),
            $this->createSubject(),
            Html::tag('span', ['class' => 'state-text'], $this->state->getStateTextTranslated())
        ));

        if ($this->state->affects_children) {
            $total = (int) $this->item->affected_children;

            if ($total > 1000) {
                $total = '1000+';
                $tooltip = $this->translate('Up to 1000+ affected objects');
            } else {
                $tooltip = sprintf(
                    $this->translatePlural(
                        '%d affected object',
                        'Up to %d affected objects',
                        $total
                    ),
                    $total
                );
            }

            $icon = new Icon(Icons::UNREACHABLE);

            $title->addHtml(new HtmlElement(
                'span',
                Attributes::create([
                    'class' => 'affected-objects',
                    'title' => $tooltip
                ]),
                $icon,
                Text::create($total)
            ));
        }
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $stateBall = new StateBall($this->state->getStateText(), $this->getStateBallSize());
        $stateBall->add($this->state->getIcon());
        if ($this->state->is_problem && ($this->state->is_handled || ! $this->state->is_reachable)) {
            $stateBall->getAttributes()->add('class', 'handled');
        }

        $visual->addHtml($stateBall);
        if ($this->state->state_type === 'soft') {
            $visual->addHtml(
                new CheckAttempt((int) $this->state->check_attempt, (int) $this->item->max_check_attempts)
            );
        }
    }

    protected function createTimestamp(): ?BaseHtmlElement
    {
        $since = null;
        if ($this->state->is_overdue) {
            $since = new TimeSince($this->state->next_update->getTimestamp());
            $since->prepend($this->translate('Overdue') . ' ');
            $since->prependHtml(new Icon(Icons::WARNING));
        } elseif ($this->state->last_state_change !== null && $this->state->last_state_change->getTimestamp() > 0) {
            $since = new TimeSince($this->state->last_state_change->getTimestamp());
        }

        return $since;
    }

    protected function assembleIconImage(BaseHtmlElement $iconImage): void
    {
        if (isset($this->item->icon_image->icon_image)) {
            $iconImage->addHtml(new IconImage($this->item->icon_image->icon_image, $this->item->icon_image_alt));
        } else {
            $iconImage->addAttributes(['class' => 'placeholder']);
        }
    }
}
