<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\View;

use Icinga\Date\DateFormatter;
use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\HostStates;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Common\ServiceStates;
use Icinga\Module\Icingadb\Common\TicketLinks;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Util\PluginOutput;
use Icinga\Module\Icingadb\Widget\CheckAttempt;
use Icinga\Module\Icingadb\Widget\MarkdownLine;
use Icinga\Module\Icingadb\Widget\PluginOutputContainer;
use Icinga\Module\Icingadb\Widget\StateChange;
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

/** @implements ItemRenderer<History> */
class EventRenderer implements ItemRenderer
{
    use Translation;
    use TicketLinks;
    use HostLink;
    use ServiceLink;

    /** @var NotificationRenderer To render NotificationHistory event */
    protected $notificationRenderer;

    public function __construct()
    {
        $this->notificationRenderer = new NotificationRenderer();
    }

    public function assembleAttributes($item, Attributes $attributes, string $layout): void
    {
        $attributes->get('class')->addValue('history');
    }

    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        $ballSize = StateBall::SIZE_LARGE;
        if ($layout === 'minimal' || $layout === 'header') {
            $ballSize = StateBall::SIZE_BIG;
        }

        switch ($item->event_type) {
            case 'comment_add':
                $visual->addHtml(
                    HtmlElement::create(
                        'div',
                        ['class' => ['icon-ball', 'ball-size-' . $ballSize]],
                        new Icon(Icons::COMMENT)
                    )
                );

                break;
            case 'comment_remove':
            case 'downtime_end':
            case 'ack_clear':
                $visual->addHtml(
                    HtmlElement::create(
                        'div',
                        ['class' => ['icon-ball', 'ball-size-' . $ballSize]],
                        new Icon(Icons::REMOVE)
                    )
                );

                break;
            case 'downtime_start':
                $visual->addHtml(
                    HtmlElement::create(
                        'div',
                        ['class' => ['icon-ball', 'ball-size-' . $ballSize]],
                        new Icon(Icons::IN_DOWNTIME)
                    )
                );

                break;
            case 'ack_set':
                $visual->addHtml(
                    HtmlElement::create(
                        'div',
                        ['class' => ['icon-ball', 'ball-size-' . $ballSize]],
                        new Icon(Icons::IS_ACKNOWLEDGED)
                    )
                );

                break;
            case 'flapping_end':
            case 'flapping_start':
                $visual->addHtml(
                    HtmlElement::create(
                        'div',
                        ['class' => ['icon-ball', 'ball-size-' . $ballSize]],
                        new Icon(Icons::IS_FLAPPING)
                    )
                );

                break;
            case 'notification':
                $visual->addHtml(
                    HtmlElement::create(
                        'div',
                        ['class' => ['icon-ball', 'ball-size-' . $ballSize]],
                        new Icon(Icons::NOTIFICATION)
                    )
                );

                break;
            case 'state_change':
                if ($item->state->state_type === 'soft') {
                    $stateType = 'soft_state';
                    $previousStateType = 'previous_soft_state';

                    if ($item->state->previous_soft_state === 0) {
                        $previousStateType = 'hard_state';
                    }

                    if ($layout !== 'minimal' && $layout !== 'header') {
                        $visual->addHtml(
                            new CheckAttempt(
                                (int) $item->state->check_attempt,
                                (int) $item->state->max_check_attempts
                            )
                        );
                    }
                } else {
                    $stateType = 'hard_state';
                    $previousStateType = 'previous_hard_state';

                    if ($item->state->hard_state === $item->state->previous_hard_state) {
                        $previousStateType = 'previous_soft_state';
                    }
                }

                if ($item->object_type === 'host') {
                    $state = HostStates::text($item->state->$stateType);
                    $previousState = HostStates::text($item->state->$previousStateType);
                } else {
                    $state = ServiceStates::text($item->state->$stateType);
                    $previousState = ServiceStates::text($item->state->$previousStateType);
                }

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

                $visual->prependHtml($stateChange);

                break;
        }
    }

    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        if ($item->event_type === 'notification' && isset($item->notification->id)) {
            $item->notification->history = $item;
            $item->notification->host = $item->host;
            $item->notification->service = $item->service;

            $this->notificationRenderer->assembleTitle($item->notification, $title, $layout);

            return;
        }

        switch ($item->event_type) {
            case 'comment_add':
                $subjectLabel = $this->translate('Comment added');

                break;
            case 'comment_remove':
                if (! empty($item->comment->removed_by)) {
                    if ($item->comment->removed_by !== $item->comment->author) {
                        $subjectLabel = sprintf(
                            $this->translate('Comment removed by %s', '..<username>'),
                            $item->comment->removed_by
                        );
                    } else {
                        $subjectLabel = $this->translate('Comment removed by author');
                    }
                } elseif (isset($item->comment->expire_time)) {
                    $subjectLabel = $this->translate('Comment expired');
                } else {
                    $subjectLabel = $this->translate('Comment removed');
                }

                break;
            case 'downtime_end':
                if (! empty($item->downtime->cancelled_by)) {
                    if ($item->downtime->cancelled_by !== $item->downtime->author) {
                        $subjectLabel = sprintf(
                            $this->translate('Downtime cancelled by %s', '..<username>'),
                            $item->downtime->cancelled_by
                        );
                    } else {
                        $subjectLabel = $this->translate('Downtime cancelled by author');
                    }
                } elseif ($item->downtime->has_been_cancelled === 'y') {
                    $subjectLabel = $this->translate('Downtime cancelled');
                } else {
                    $subjectLabel = $this->translate('Downtime ended');
                }

                break;
            case 'downtime_start':
                $subjectLabel = $this->translate('Downtime started');

                break;
            case 'flapping_start':
                $subjectLabel = $this->translate('Flapping started');

                break;
            case 'flapping_end':
                $subjectLabel = $this->translate('Flapping stopped');

                break;
            case 'ack_set':
                $subjectLabel = $this->translate('Acknowledgement set');

                break;
            case 'ack_clear':
                if (! empty($item->acknowledgement->cleared_by)) {
                    if ($item->acknowledgement->cleared_by !== $item->acknowledgement->author) {
                        $subjectLabel = sprintf(
                            $this->translate('Acknowledgement cleared by %s', '..<username>'),
                            $item->acknowledgement->cleared_by
                        );
                    } else {
                        $subjectLabel = $this->translate('Acknowledgement cleared by author');
                    }
                } elseif (isset($item->acknowledgement->expire_time)) {
                    $subjectLabel = $this->translate('Acknowledgement expired');
                } else {
                    $subjectLabel = $this->translate('Acknowledgement cleared');
                }

                break;
            case 'state_change':
                $state = $item->state->state_type === 'hard'
                    ? $item->state->hard_state
                    : $item->state->soft_state;
                if ($state === 0) {
                    if ($item->object_type === 'service') {
                        $subjectLabel = $this->translate('Service recovered');
                    } else {
                        $subjectLabel = $this->translate('Host recovered');
                    }
                } else {
                    if ($item->state->state_type === 'hard') {
                        $subjectLabel = $this->translate('Hard state changed');
                    } else {
                        $subjectLabel = $this->translate('Soft state changed');
                    }
                }

                break;
            default:
                $subjectLabel = $item->event_type;

                break;
        }

        if ($layout === 'header') {
            $title->addHtml(HtmlElement::create('span', ['class' => 'subject'], $subjectLabel));
        } else {
            $title->addHtml(new Link($subjectLabel, Links::event($item), ['class' => 'subject']));
        }

        if ($item->object_type === 'host' && isset($item->host->id)) {
            $link = $this->createHostLink($item->host, true);
        } elseif (isset($item->host->id, $item->service->id)) {
            $link = $this->createServiceLink($item->service, $item->host, true);
        }

        $title->addHtml(Text::create(' '));
        if (isset($link)) {
            $title->addHtml($link);
        }
    }

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
        if ($item->event_type === 'notification') {
            $item->notification->host = $item->host;
            $item->notification->service = $item->service;

            $this->notificationRenderer->assembleCaption($item->notification, $caption, $layout);

            return;
        }

        switch ($item->event_type) {
            case 'comment_add':
            case 'comment_remove':
                $markdownLine = new MarkdownLine($this->createTicketLinks($item->comment->comment));
                $caption->getAttributes()->add($markdownLine->getAttributes());
                $caption->add([
                    new Icon(Icons::USER),
                    $item->comment->author,
                    ': '
                ])->addFrom($markdownLine);

                break;
            case 'downtime_end':
            case 'downtime_start':
                $markdownLine = new MarkdownLine($this->createTicketLinks($item->downtime->comment));
                $caption->getAttributes()->add($markdownLine->getAttributes());
                $caption->add([
                    new Icon(Icons::USER),
                    $item->downtime->author,
                    ': '
                ])->addFrom($markdownLine);

                break;
            case 'flapping_start':
                $caption
                    ->add(
                        sprintf(
                            $this->translate('State Change Rate: %.2f%%; Start Threshold: %.2f%%'),
                            $item->flapping->percent_state_change_start,
                            $item->flapping->flapping_threshold_high
                        )
                    )
                    ->getAttributes()
                    ->add('class', 'plugin-output');

                break;
            case 'flapping_end':
                $caption
                    ->add(
                        sprintf(
                            $this->translate('State Change Rate: %.2f%%; End Threshold: %.2f%%; Flapping for %s'),
                            $item->flapping->percent_state_change_end,
                            $item->flapping->flapping_threshold_low,
                            isset($item->flapping->end_time)
                                ? DateFormatter::formatDuration(
                                    $item->flapping->end_time->getTimestamp()
                                    - $item->flapping->start_time->getTimestamp()
                                )
                                : $this->translate('n. a.')
                        )
                    )
                    ->getAttributes()
                    ->add('class', 'plugin-output');

                break;
            case 'ack_clear':
            case 'ack_set':
                if (! isset($item->acknowledgement->comment) && ! isset($item->acknowledgement->author)) {
                    $caption->addHtml(
                        new EmptyState(
                            $this->translate('This acknowledgement was set before Icinga DB history recording')
                        )
                    );
                } else {
                    $markdownLine = new MarkdownLine($this->createTicketLinks($item->acknowledgement->comment));
                    $caption->getAttributes()->add($markdownLine->getAttributes());
                    $caption->add([
                        new Icon(Icons::USER),
                        $item->acknowledgement->author,
                        ': '
                    ])->addFrom($markdownLine);
                }

                break;
            case 'state_change':
                $commandName = $item->object_type === 'host'
                    ? $item->host->checkcommand_name
                    : $item->service->checkcommand_name;
                if (isset($commandName)) {
                    if (empty($item->state->output)) {
                        $caption->addHtml(new EmptyState($this->translate('Output unavailable.')));
                    } else {
                        $caption->addHtml(
                            new PluginOutputContainer(
                                (new PluginOutput($item->state->output))
                                    ->setCommandName($commandName)
                            )
                        );
                    }
                } else {
                    $caption->addHtml(
                        new EmptyState($this->translate('Waiting for Icinga DB to synchronize the config.'))
                    );
                }

                break;
        }
    }

    public function assembleExtendedInfo($item, HtmlDocument $info, string $layout): void
    {
        $info->addHtml(new TimeAgo($item->event_time->getTimestamp()));
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
    }

    public function assemble($item, string $name, HtmlDocument $element, string $layout): bool
    {
        return false; // no custom sections
    }
}
