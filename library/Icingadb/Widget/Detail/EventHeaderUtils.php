<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

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
use Icinga\Module\Icingadb\Widget\ItemList\NotificationListItem;
use Icinga\Module\Icingadb\Widget\MarkdownLine;
use Icinga\Module\Icingadb\Widget\PluginOutputContainer;
use Icinga\Module\Icingadb\Widget\StateChange;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;

trait EventHeaderUtils
{
    use Translation;
    use HostLink;
    use ServiceLink;
    use TicketLinks;

    /**
     * Get the object
     *
     * @return History
     */
    abstract protected function getObject(): History;

    /**
     * Get the state ball size
     *
     * @return string
     */
    abstract protected function getStateBallSize(): string;

    /**
     * Whether to create a subject link
     *
     * @return bool
     */
    abstract protected function wantSubjectLink(): bool;

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $event = $this->getObject();
        switch ($event->event_type) {
            case 'comment_add':
                $visual->addHtml(
                    HtmlElement::create(
                        'div',
                        ['class' => ['icon-ball', 'ball-size-' . $this->getStateBallSize()]],
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
                        ['class' => ['icon-ball', 'ball-size-' . $this->getStateBallSize()]],
                        new Icon(Icons::REMOVE)
                    )
                );

                break;
            case 'downtime_start':
                $visual->addHtml(
                    HtmlElement::create(
                        'div',
                        ['class' => ['icon-ball', 'ball-size-' . $this->getStateBallSize()]],
                        new Icon(Icons::IN_DOWNTIME)
                    )
                );

                break;
            case 'ack_set':
                $visual->addHtml(
                    HtmlElement::create(
                        'div',
                        ['class' => ['icon-ball', 'ball-size-' . $this->getStateBallSize()]],
                        new Icon(Icons::IS_ACKNOWLEDGED)
                    )
                );

                break;
            case 'flapping_end':
            case 'flapping_start':
                $visual->addHtml(
                    HtmlElement::create(
                        'div',
                        ['class' => ['icon-ball', 'ball-size-' . $this->getStateBallSize()]],
                        new Icon(Icons::IS_FLAPPING)
                    )
                );

                break;
            case 'notification':
                $visual->addHtml(
                    HtmlElement::create(
                        'div',
                        ['class' => ['icon-ball', 'ball-size-' . $this->getStateBallSize()]],
                        new Icon(Icons::NOTIFICATION)
                    )
                );

                break;
            case 'state_change':
                if ($event->state->state_type === 'soft') {
                    $stateType = 'soft_state';
                    $previousStateType = 'previous_soft_state';

                    if ($event->state->previous_soft_state === 0) {
                        $previousStateType = 'hard_state';
                    }

                    $visual->addHtml(
                        new CheckAttempt(
                            (int)$event->state->check_attempt,
                            (int)$event->state->max_check_attempts
                        )
                    );
                } else {
                    $stateType = 'hard_state';
                    $previousStateType = 'previous_hard_state';

                    if ($event->state->hard_state === $event->state->previous_hard_state) {
                        $previousStateType = 'previous_soft_state';
                    }
                }

                if ($event->object_type === 'host') {
                    $state = HostStates::text($event->state->$stateType);
                    $previousState = HostStates::text($event->state->$previousStateType);
                } else {
                    $state = ServiceStates::text($event->state->$stateType);
                    $previousState = ServiceStates::text($event->state->$previousStateType);
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

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $event = $this->getObject();
        switch ($event->event_type) {
            case 'comment_add':
                $subjectLabel = $this->translate('Comment added');

                break;
            case 'comment_remove':
                if (! empty($event->comment->removed_by)) {
                    if ($event->comment->removed_by !== $event->comment->author) {
                        $subjectLabel = sprintf(
                            $this->translate('Comment removed by %s', '..<username>'),
                            $event->comment->removed_by
                        );
                    } else {
                        $subjectLabel = $this->translate('Comment removed by author');
                    }
                } elseif (isset($event->comment->expire_time)) {
                    $subjectLabel = $this->translate('Comment expired');
                } else {
                    $subjectLabel = $this->translate('Comment removed');
                }

                break;
            case 'downtime_end':
                if (! empty($event->downtime->cancelled_by)) {
                    if ($event->downtime->cancelled_by !== $event->downtime->author) {
                        $subjectLabel = sprintf(
                            $this->translate('Downtime cancelled by %s', '..<username>'),
                            $event->downtime->cancelled_by
                        );
                    } else {
                        $subjectLabel = $this->translate('Downtime cancelled by author');
                    }
                } elseif ($event->downtime->has_been_cancelled === 'y') {
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
                if (! empty($event->acknowledgement->cleared_by)) {
                    if ($event->acknowledgement->cleared_by !== $event->acknowledgement->author) {
                        $subjectLabel = sprintf(
                            $this->translate('Acknowledgement cleared by %s', '..<username>'),
                            $event->acknowledgement->cleared_by
                        );
                    } else {
                        $subjectLabel = $this->translate('Acknowledgement cleared by author');
                    }
                } elseif (isset($event->acknowledgement->expire_time)) {
                    $subjectLabel = $this->translate('Acknowledgement expired');
                } else {
                    $subjectLabel = $this->translate('Acknowledgement cleared');
                }

                break;
            case 'notification':
                $subjectLabel = isset($event->notification->type) ? sprintf(
                    NotificationListItem::phraseForType($event->notification->type),
                    ucfirst($event->object_type)
                ) : $event->event_type;

                break;
            case 'state_change':
                $state = $event->state->state_type === 'hard'
                    ? $event->state->hard_state
                    : $event->state->soft_state;
                if ($state === 0) {
                    if ($event->object_type === 'service') {
                        $subjectLabel = $this->translate('Service recovered');
                    } else {
                        $subjectLabel = $this->translate('Host recovered');
                    }
                } else {
                    if ($event->state->state_type === 'hard') {
                        $subjectLabel = $this->translate('Hard state changed');
                    } else {
                        $subjectLabel = $this->translate('Soft state changed');
                    }
                }

                break;
            default:
                $subjectLabel = $event->event_type;

                break;
        }

        if (! $this->wantSubjectLink()) {
            $title->addHtml(HtmlElement::create('span', ['class' => 'subject'], $subjectLabel));
        } else {
            $title->addHtml(new Link($subjectLabel, Links::event($event), ['class' => 'subject']));
        }

        if ($event->object_type === 'host') {
            if (isset($event->host->id)) {
                $link = $this->createHostLink($event->host, true);
            }
        } else {
            if (isset($event->host->id, $event->service->id)) {
                $link = $this->createServiceLink($event->service, $event->host, true);
            }
        }

        $title->addHtml(Text::create(' '));
        if (isset($link)) {
            $title->addHtml($link);
        }
    }

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $event = $this->getObject();
        switch ($event->event_type) {
            case 'comment_add':
            case 'comment_remove':
                $markdownLine = new MarkdownLine($this->createTicketLinks($event->comment->comment));
                $caption->getAttributes()->add($markdownLine->getAttributes());
                $caption->add([
                    new Icon(Icons::USER),
                    $event->comment->author,
                    ': '
                ])->addFrom($markdownLine);

                break;
            case 'downtime_end':
            case 'downtime_start':
                $markdownLine = new MarkdownLine($this->createTicketLinks($event->downtime->comment));
                $caption->getAttributes()->add($markdownLine->getAttributes());
                $caption->add([
                    new Icon(Icons::USER),
                    $event->downtime->author,
                    ': '
                ])->addFrom($markdownLine);

                break;
            case 'flapping_start':
                $caption
                    ->add(
                        sprintf(
                            t('State Change Rate: %.2f%%; Start Threshold: %.2f%%'),
                            $event->flapping->percent_state_change_start,
                            $event->flapping->flapping_threshold_high
                        )
                    )
                    ->getAttributes()
                    ->add('class', 'plugin-output');

                break;
            case 'flapping_end':
                $caption
                    ->add(
                        sprintf(
                            t('State Change Rate: %.2f%%; End Threshold: %.2f%%; Flapping for %s'),
                            $event->flapping->percent_state_change_end,
                            $event->flapping->flapping_threshold_low,
                            isset($event->flapping->end_time)
                                ? DateFormatter::formatDuration(
                                    $event->flapping->end_time->getTimestamp()
                                    - $event->flapping->start_time->getTimestamp()
                                )
                                : t('n. a.')
                        )
                    )
                    ->getAttributes()
                    ->add('class', 'plugin-output');

                break;
            case 'ack_clear':
            case 'ack_set':
                if (! isset($event->acknowledgement->comment) && ! isset($event->acknowledgement->author)) {
                    $caption->addHtml(
                        new EmptyState(
                            $this->translate('This acknowledgement was set before Icinga DB history recording')
                        )
                    );
                } else {
                    $markdownLine = new MarkdownLine($this->createTicketLinks($event->acknowledgement->comment));
                    $caption->getAttributes()->add($markdownLine->getAttributes());
                    $caption->add([
                        new Icon(Icons::USER),
                        $event->acknowledgement->author,
                        ': '
                    ])->addFrom($markdownLine);
                }

                break;
            case 'notification':
                if (! empty($event->notification->author)) {
                    $caption->add([
                        new Icon(Icons::USER),
                        $event->notification->author,
                        ': ',
                        $event->notification->text
                    ]);
                } else {
                    $commandName = $event->object_type === 'host'
                        ? $event->host->checkcommand_name
                        : $event->service->checkcommand_name;
                    if (isset($commandName)) {
                        if (empty($event->notification->text)) {
                            $caption->addHtml(new EmptyState($this->translate('Output unavailable.')));
                        } else {
                            $caption->addHtml(
                                new PluginOutputContainer(
                                    (new PluginOutput($event->notification->text))
                                        ->setCommandName($commandName)
                                )
                            );
                        }
                    } else {
                        $caption->addHtml(
                            new EmptyState($this->translate('Waiting for Icinga DB to synchronize the config.'))
                        );
                    }
                }

                break;
            case 'state_change':
                $commandName = $event->object_type === 'host'
                    ? $event->host->checkcommand_name
                    : $event->service->checkcommand_name;
                if (isset($commandName)) {
                    if (empty($event->state->output)) {
                        $caption->addHtml(new EmptyState($this->translate('Output unavailable.')));
                    } else {
                        $caption->addHtml(
                            new PluginOutputContainer(
                                (new PluginOutput($event->state->output))
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

    protected function createTimestamp(): ?BaseHtmlElement
    {
        return new TimeAgo($this->getObject()->event_time->getTimestamp());
    }
}
