<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Util\PluginOutput;
use Icinga\Module\Icingadb\Widget\CheckAttempt;
use Icinga\Module\Icingadb\Widget\IconImage;
use Icinga\Module\Icingadb\Widget\PluginOutputContainer;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Orm\Model;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeSince;

trait HostAndServiceHeaderUtils
{
    use Translation;

    /**
     * Get the object
     *
     * @return Host|Service
     */
    abstract protected function getObject(): Model;

    /**
     * Create the subject
     *
     * @return ValidHtml
     */
    abstract protected function createSubject(): ValidHtml;

    /**
     * Get the state ball size
     *
     * @return string
     */
    abstract protected function getStateBallSize(): string;

    /**
     * Whether to show the icon image/placeholder
     *
     * @return bool When false, no icon image or placeholder will be shown
     */
    abstract protected function wantIconImage(): bool;

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $object = $this->getObject();
        $state = $object->state;

        $stateBall = new StateBall($state->getStateText(), $this->getStateBallSize());
        $stateBall->add($state->getIcon());
        if ($state->is_problem && ($state->is_handled || ! $state->is_reachable)) {
            $stateBall->getAttributes()->add('class', 'handled');
        }

        $visual->addHtml($stateBall);
        if ($state->state_type === 'soft') {
            $visual->addHtml(
                new CheckAttempt((int) $state->check_attempt, (int) $object->max_check_attempts)
            );
        }
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $object = $this->getObject();
        $state = $object->state;
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<hostname> is <state-text>'),
            $this->createSubject(),
            Html::tag('span', ['class' => 'state-text'], $state->getStateTextTranslated())
        ));

        if ($state->affects_children) {
            $total = (int) $object->affected_children;

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

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $object = $this->getObject();
        $state = $object->state;
        if ($state->soft_state === null && $state->output === null) {
            $caption->addHtml(Text::create($this->translate('Waiting for Icinga DB to synchronize the state.')));
        } else {
            if (empty($state->output)) {
                $pluginOutput = new EmptyState($this->translate('Output unavailable.'));
            } else {
                $pluginOutput = new PluginOutputContainer(PluginOutput::fromObject($object));
            }

            $caption->addHtml($pluginOutput);
        }
    }

    protected function createTimestamp(): ?BaseHtmlElement
    {
        $state =  $this->getObject()->state;
        $since = null;
        if ($state->is_overdue) {
            $since = new TimeSince($state->next_update->getTimestamp());
            $since->prepend($this->translate('Overdue') . ' ');
            $since->prependHtml(new Icon(Icons::WARNING));
        } elseif ($state->last_state_change !== null && $state->last_state_change->getTimestamp() > 0) {
            $since = new TimeSince($state->last_state_change->getTimestamp());
        }

        return $since;
    }

    protected function createIconImage(): ?BaseHtmlElement
    {
        if (! $this->wantIconImage()) {
            return null;
        }

        $iconImage = HtmlElement::create('div', [
            'class' => 'icon-image'
        ]);

        $object = $this->getObject();
        if (isset($object->icon_image->icon_image)) {
            $iconImage->addHtml(new IconImage($object->icon_image->icon_image, $object->icon_image_alt));
        } else {
            $iconImage->addAttributes(['class' => 'placeholder']);
        }

        return $iconImage;
    }
}
