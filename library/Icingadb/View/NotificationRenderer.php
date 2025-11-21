<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\View;

use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\HostStates;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Common\ServiceStates;
use Icinga\Module\Icingadb\Model\NotificationHistory;
use Icinga\Module\Icingadb\Util\PluginOutput;
use Icinga\Module\Icingadb\Widget\PluginOutputContainer;
use Icinga\Module\Icingadb\Widget\StateChange;
use InvalidArgumentException;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Common\ItemRenderer;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;

/** @implements ItemRenderer<NotificationHistory> */
class NotificationRenderer implements ItemRenderer
{
    use Translation;
    use HostLink;
    use ServiceLink;

    public function assembleAttributes($item, Attributes $attributes, string $layout): void
    {
        $attributes->get('class')->addValue('notification');
    }

    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        $ballSize = StateBall::SIZE_LARGE;
        if ($layout === 'minimal' || $layout === 'header') {
            $ballSize = StateBall::SIZE_BIG;
        }

        switch ($item->type) {
            case 'acknowledgement':
                $visual->addHtml(HtmlElement::create(
                    'div',
                    ['class' => ['icon-ball', 'ball-size-' . $ballSize]],
                    new Icon(Icons::IS_ACKNOWLEDGED)
                ));

                break;
            case 'custom':
                $visual->addHtml(HtmlElement::create(
                    'div',
                    ['class' => ['icon-ball', 'ball-size-' . $ballSize]],
                    new Icon(Icons::NOTIFICATION)
                ));

                break;
            case 'downtime_end':
            case 'downtime_removed':
            case 'downtime_start':
                $visual->addHtml(HtmlElement::create(
                    'div',
                    ['class' => ['icon-ball', 'ball-size-' . $ballSize]],
                    new Icon(Icons::IN_DOWNTIME)
                ));

                break;
            case 'flapping_end':
            case 'flapping_start':
                $visual->addHtml(HtmlElement::create(
                    'div',
                    ['class' => ['icon-ball', 'ball-size-' . $ballSize]],
                    new Icon(Icons::IS_FLAPPING)
                ));

                break;
            case 'problem':
            case 'recovery':
                if ($item->object_type === 'host') {
                    $state = HostStates::text($item->state);
                    $previousHardState = HostStates::text($item->previous_hard_state);
                } else {
                    $state = ServiceStates::text($item->state);
                    $previousHardState = ServiceStates::text($item->previous_hard_state);
                }

                $visual->addHtml(new StateChange($state, $previousHardState));

                break;
        }
    }

    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        if ($layout === 'header') {
            $title->addHtml(HtmlElement::create(
                'span',
                ['class' => 'subject'],
                sprintf($this->phraseForType($item->type), ucfirst($item->object_type))
            ));
        } else {
            $title->addHtml(new Link(
                sprintf($this->phraseForType($item->type), ucfirst($item->object_type)),
                Links::event($item->history),
                ['class' => 'subject']
            ));
        }

        if ($item->object_type === 'host') {
            $link = $this->createHostLink($item->host, true);
        } else {
            $link = $this->createServiceLink($item->service, $item->host, true);
        }

        $title->addHtml(Text::create(' '), $link);
    }

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
        if (in_array($item->type, ['flapping_end', 'flapping_start', 'problem', 'recovery'])) {
            $commandName = $item->object_type === 'host'
                ? $item->host->checkcommand_name
                : $item->service->checkcommand_name;
            if (isset($commandName)) {
                if (empty($item->text)) {
                    $caption->addHtml(new EmptyState($this->translate('Output unavailable.')));
                } else {
                    $caption->addHtml(new PluginOutputContainer(
                        (new PluginOutput($item->text))
                            ->setCommandName($commandName)
                    ));
                }
            } else {
                $caption->addHtml(new EmptyState($this->translate('Waiting for Icinga DB to synchronize the config.')));
            }
        } else {
            $caption->add([
                new Icon(Icons::USER),
                $item->author,
                ': ',
                $item->text
            ]);
        }
    }

    public function assembleExtendedInfo($item, HtmlDocument $info, string $layout): void
    {
        $info->addHtml(new TimeAgo($item->send_time->getTimestamp()));
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
    }

    public function assemble($item, string $name, HtmlDocument $element, string $layout): bool
    {
        return false; // no custom sections
    }

    /**
     * Get a localized phrase for the given notification type
     *
     * @param string $type
     *
     * @return string
     */
    protected function phraseForType(string $type): string
    {
        switch ($type) {
            case 'acknowledgement':
                return $this->translate('Problem acknowledged');
            case 'custom':
                return $this->translate('Custom Notification triggered');
            case 'downtime_end':
                return $this->translate('Downtime ended');
            case 'downtime_removed':
                return $this->translate('Downtime removed');
            case 'downtime_start':
                return $this->translate('Downtime started');
            case 'flapping_end':
                return $this->translate('Flapping stopped');
            case 'flapping_start':
                return $this->translate('Flapping started');
            case 'problem':
                return $this->translate('%s ran into a problem');
            case 'recovery':
                return $this->translate('%s recovered');
            default:
                throw new InvalidArgumentException(sprintf('Type %s is not a valid notification type', $type));
        }
    }
}
