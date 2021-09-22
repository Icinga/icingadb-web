<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\BaseListItem;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Model\State;
use Icinga\Module\Icingadb\Util\PluginOutput;
use Icinga\Module\Icingadb\Widget\CheckAttempt;
use Icinga\Module\Icingadb\Widget\EmptyState;
use Icinga\Module\Icingadb\Widget\PluginOutputContainer;
use ipl\Web\Url;
use ipl\Web\Widget\TimeSince;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
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
            $src = $this->item->icon_image->icon_image;

            if (strpos($src, '/') === false) {
                $src = 'img/icons/' . $src;
            }

            if (getenv('ICINGAWEB_EXPORT_FORMAT') === 'pdf') {
                $srcUrl = Url::fromPath($src);
                $path = $srcUrl->getRelativeUrl();
                if (! $srcUrl->isExternal() && file_exists($path) && is_file($path)) {
                    $mimeType = @mime_content_type($path);
                    $content = @file_get_contents($path);
                    if ($mimeType !== false && $content !== false) {
                        $src = "data:$mimeType;base64," . base64_encode($content);
                    }
                }
            }

            $iconImage->add(HtmlElement::create('img', [
                'src' => $src,
                'alt' => $this->item->icon_image_alt
            ]));
        } else {
            $iconImage->addAttributes(['class' => 'placeholder']);
        }
    }

    protected function assembleTitle(BaseHtmlElement $title)
    {
        $title->add(Html::sprintf(
            t('%s is %s', '<hostname> is <state-text>'),
            $this->createSubject(),
            Html::tag('span', ['class' => 'state-text'], $this->state->getStateTextTranslated())
        ));
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
            $since = new TimeSince($this->state->next_update);
            $since->prepend(t('Overdue') . ' ');
            $since->prepend(new Icon(Icons::WARNING));
            return $since;
        } elseif ($this->state->last_state_change !== null) {
            return new TimeSince($this->state->last_state_change);
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
