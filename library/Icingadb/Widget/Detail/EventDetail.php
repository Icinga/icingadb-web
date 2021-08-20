<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use DateTime;
use DateTimeZone;
use Icinga\Date\DateFormatter;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\HostStates;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Widget\MarkdownText;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Common\ServiceStates;
use Icinga\Module\Icingadb\Model\AcknowledgementHistory;
use Icinga\Module\Icingadb\Model\CommentHistory;
use Icinga\Module\Icingadb\Model\DowntimeHistory;
use Icinga\Module\Icingadb\Model\FlappingHistory;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Model\NotificationHistory;
use Icinga\Module\Icingadb\Model\StateHistory;
use Icinga\Module\Icingadb\Util\PluginOutput;
use Icinga\Module\Icingadb\Widget\EmptyState;
use Icinga\Module\Icingadb\Widget\HorizontalKeyValue;
use Icinga\Module\Icingadb\Widget\ItemList\UserList;
use Icinga\Module\Icingadb\Widget\PluginOutputContainer;
use Icinga\Module\Icingadb\Widget\ShowMore;
use ipl\Html\BaseHtmlElement;
use ipl\Html\FormattedString;
use ipl\Html\HtmlElement;
use ipl\Html\TemplateString;
use ipl\Html\Text;
use ipl\Orm\ResultSet;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Str;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;

class EventDetail extends BaseHtmlElement
{
    use Auth;
    use Database;
    use HostLink;
    use ServiceLink;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'event-detail'];

    /** @var History */
    protected $event;

    public function __construct(History $event)
    {
        $this->event = $event;
    }

    protected function assembleNotificationEvent(NotificationHistory $notification)
    {
        $this->addHtml(
            HtmlElement::create('h2', null, $notification->author ? t('Comment') : t('Plugin Output')),
            HtmlElement::create('div', [
                'id'    => 'check-output-' . (
                    $notification->object_type === 'host'
                        ? $this->event->host->checkcommand
                        : $this->event->service->checkcommand
                ),
                'class' => 'collapsible',
                'data-visible-height' => 100
            ], new PluginOutputContainer(
                (new PluginOutput($notification->text))
                    ->setCommandName($notification->object_type === 'host'
                        ? $this->event->host->checkcommand
                        : $this->event->service->checkcommand)
            ))
        );

        if ($notification->object_type === 'host') {
            $objectKey = t('Host');
            $objectInfo = HtmlElement::create('span', ['class' => 'accompanying-text'], [
                HtmlElement::create('span', ['class' => 'state-change'], [
                    new StateBall(HostStates::text($notification->previous_hard_state), StateBall::SIZE_MEDIUM),
                    new StateBall(HostStates::text($notification->state), StateBall::SIZE_MEDIUM)
                ]),
                ' ',
                HtmlElement::create('span', ['class' => 'subject'], $this->event->host->display_name)
            ]);
        } else {
            $objectKey = t('Service');
            $objectInfo = HtmlElement::create('span', ['class' => 'accompanying-text'], [
                HtmlElement::create('span', ['class' => 'state-change'], [
                    new StateBall(ServiceStates::text($notification->previous_hard_state), StateBall::SIZE_MEDIUM),
                    new StateBall(ServiceStates::text($notification->state), StateBall::SIZE_MEDIUM)
                ]),
                ' ',
                FormattedString::create(
                    t('%s on %s', '<service> on <host>'),
                    HtmlElement::create('span', ['class' => 'subject'], $this->event->service->display_name),
                    HtmlElement::create('span', ['class' => 'subject'], $this->event->host->display_name)
                )
            ]);
        }

        $this->addHtml(
            new HtmlElement('h2', null, Text::create(t('Event Info'))),
            new HorizontalKeyValue(t('Sent On'), DateFormatter::formatDateTime($notification->send_time))
        );
        if ($notification->author) {
            $this->addHtml(new HorizontalKeyValue(t('Sent by'), [
                new Icon('user'),
                $notification->author
            ]));
        }

        $this->addHtml(
            new HorizontalKeyValue(t('Type'), ucfirst(Str::camel($notification->type))),
            new HorizontalKeyValue(t('State'), $notification->object_type === 'host'
                ? ucfirst(HostStates::text($notification->state))
                : ucfirst(ServiceStates::text($notification->state))),
            new HorizontalKeyValue($objectKey, $objectInfo)
        );

        $this->addHtml(new HtmlElement('h2', null, Text::create(t('Notified Users'))));
        if ($notification->users_notified === 0) {
            $this->addHtml(new EmptyState(t('None', 'notified users: none')));
        } elseif (! $this->isPermittedRoute('users')) {
            $this->addHtml(Text::create(sprintf(tp(
                'This notification received a single user',
                'This notification received %d users',
                $notification->users_notified
            ), $notification->users_notified)));
        } elseif ($notification->users_notified > 0) {
            $users = $notification->user
                ->limit(5)
                ->peekAhead();

            $users = $users->execute();
            /** @var ResultSet $users */

            $this->addHtml(
                new UserList($users),
                (new ShowMore($users, Links::users()->addParams([
                        'notification_history.id' => bin2hex($notification->id)
                    ]), sprintf(t('Show all %d recipients'), $notification->users_notified)))
                    ->setBaseTarget('_next')
            );
        }
    }

    protected function assembleStateChangeEvent(StateHistory $stateChange)
    {
        $this->addHtml(
            new HtmlElement('h2', null, Text::create(t('Plugin Output'))),
            HtmlElement::create('div', [
                'id'    => 'check-output-' . (
                    $stateChange->object_type === 'host'
                        ? $this->event->host->checkcommand
                        : $this->event->service->checkcommand
                ),
                'class' => 'collapsible',
                'data-visible-height' => 100
            ], new PluginOutputContainer(
                (new PluginOutput($stateChange->output . "\n" . $stateChange->long_output))
                    ->setCommandName($stateChange->object_type === 'host'
                        ? $this->event->host->checkcommand
                        : $this->event->service->checkcommand)
            ))
        );

        if ($stateChange->object_type === 'host') {
            $objectKey = t('Host');
            $objectState = $stateChange->state_type === 'hard' ? $stateChange->hard_state : $stateChange->soft_state;
            $objectInfo = HtmlElement::create('span', ['class' => 'accompanying-text'], [
                HtmlElement::create('span', ['class' => 'state-change'], [
                    new StateBall(HostStates::text($stateChange->previous_soft_state), StateBall::SIZE_MEDIUM),
                    new StateBall(HostStates::text($objectState), StateBall::SIZE_MEDIUM)
                ]),
                ' ',
                HtmlElement::create('span', ['class' => 'subject'], $this->event->host->display_name)
            ]);
        } else {
            $objectKey = t('Service');
            $objectState = $stateChange->state_type === 'hard' ? $stateChange->hard_state : $stateChange->soft_state;
            $objectInfo = HtmlElement::create('span', ['class' => 'accompanying-text'], [
                HtmlElement::create('span', ['class' => 'state-change'], [
                    new StateBall(ServiceStates::text($stateChange->previous_soft_state), StateBall::SIZE_MEDIUM),
                    new StateBall(ServiceStates::text($objectState), StateBall::SIZE_MEDIUM)
                ]),
                ' ',
                FormattedString::create(
                    t('%s on %s', '<service> on <host>'),
                    HtmlElement::create('span', ['class' => 'subject'], $this->event->service->display_name),
                    HtmlElement::create('span', ['class' => 'subject'], $this->event->host->display_name)
                )
            ]);
        }

        $this->addHtml(
            new HtmlElement('h2', null, Text::create(t('Event Info'))),
            new HorizontalKeyValue(t('Occurred On'), DateFormatter::formatDateTime($stateChange->event_time)),
            new HorizontalKeyValue(t('Check Source'), $stateChange->check_source)
        );
        if ($stateChange->state_type === 'soft') {
            $this->addHtml(new HorizontalKeyValue(t('Check Attempt'), sprintf(
                t('%d of %d'),
                $stateChange->attempt,
                $stateChange->max_check_attempts
            )));
        }
        $this->addHtml(
            new HorizontalKeyValue(t('State'), $stateChange->object_type === 'host'
                ? ucfirst(HostStates::text($objectState))
                : ucfirst(ServiceStates::text($objectState))),
            new HorizontalKeyValue(
                t('State Type'),
                $stateChange->state_type === 'hard' ? t('Hard', 'state') : t('Soft', 'state')
            ),
            new HorizontalKeyValue($objectKey, $objectInfo)
        );
    }

    protected function assembleDowntimeEvent(DowntimeHistory $downtime)
    {
        $this->addHtml(
            new HtmlElement('h2', null, Text::create(t('Comment'))),
            new MarkdownText($downtime->comment)
        );

        $this->addHtml(new HtmlElement('h2', null, Text::create(t('Event Info'))));

        if ($downtime->triggered_by_id !== null || $downtime->parent_id !== null) {
            if ($downtime->triggered_by_id !== null) {
                $label = t('Triggered By');
                $relatedDowntime = $downtime->triggered_by;
            } else {
                $label = t('Parent');
                $relatedDowntime = $downtime->parent;
            }

            $query = History::on($this->getDb())
                ->columns('id')
                ->filter(Filter::equal('event_type', 'downtime_start'))
                ->filter(Filter::equal('history.downtime_history_id', $relatedDowntime->downtime_id));
            $this->applyRestrictions($query);
            if (($relatedEvent = $query->first()) !== null) {
                /** @var History $relatedEvent */
                $this->addHtml(new HorizontalKeyValue(
                    $label,
                    HtmlElement::create('span', ['class' => 'accompanying-text'], TemplateString::create(
                        $relatedDowntime->is_flexible
                            ? t('{{#link}}Flexible Downtime{{/link}} for %s')
                            : t('{{#link}}Fixed Downtime{{/link}} for %s'),
                        ['link' => new Link(null, Links::event($relatedEvent), ['class' => 'subject'])],
                        ($relatedDowntime->object_type === 'host'
                            ? $this->createHostLink($relatedDowntime->host, true)
                            : $this->createServiceLink($relatedDowntime->service, $relatedDowntime->host, true))
                            ->addAttributes(['class' => 'subject'])
                    ))
                ));
            }
        }

        $this->addHtml(
            $downtime->object_type === 'host'
                ? new HorizontalKeyValue(t('Host'), HtmlElement::create(
                    'span',
                    ['class' => 'accompanying-text'],
                    HtmlElement::create('span', ['class' => 'subject'], $this->event->host->display_name)
                ))
                : new HorizontalKeyValue(t('Service'), HtmlElement::create(
                    'span',
                    ['class' => 'accompanying-text'],
                    FormattedString::create(
                        t('%s on %s', '<service> on <host>'),
                        HtmlElement::create('span', ['class' => 'subject'], $this->event->service->display_name),
                        HtmlElement::create('span', ['class' => 'subject'], $this->event->host->display_name)
                    )
                )),
            new HorizontalKeyValue(t('Entered On'), DateFormatter::formatDateTime($downtime->entry_time)),
            new HorizontalKeyValue(t('Author'), [new Icon('user'), $downtime->author]),
            // TODO: The following should be presented in a specific widget (maybe just like the downtime card)
            new HorizontalKeyValue(t('Triggered On'), DateFormatter::formatDateTime($downtime->trigger_time)),
            new HorizontalKeyValue(
                t('Scheduled Start'),
                DateFormatter::formatDateTime($downtime->scheduled_start_time)
            ),
            new HorizontalKeyValue(t('Actual Start'), DateFormatter::formatDateTime($downtime->start_time)),
            new HorizontalKeyValue(t('Scheduled End'), DateFormatter::formatDateTime($downtime->scheduled_end_time)),
            new HorizontalKeyValue(t('Actual End'), DateFormatter::formatDateTime($downtime->end_time))
        );
        if ($downtime->is_flexible) {
            $this->addHtml(
                new HorizontalKeyValue(t('Flexible'), t('Yes')),
                new HorizontalKeyValue(t('Duration'), DateFormatter::formatDuration($downtime->flexible_duration))
            );
        }

        if ($downtime->has_been_cancelled) {
            $this->addHtml(
                new HtmlElement('h2', null, Text::create(t('This downtime has been cancelled'))),
                new HorizontalKeyValue(t('Cancelled On'), DateFormatter::formatDateTime($downtime->cancel_time)),
                new HorizontalKeyValue(t('Cancelled by'), [new Icon('user'), $downtime->cancelled_by])
            );
        }
    }

    protected function assembleCommentEvent(CommentHistory $comment)
    {
        $this->addHtml(
            new HtmlElement('h2', null, Text::create(t('Comment'))),
            new MarkdownText($comment->comment)
        );

        $this->addHtml(
            new HtmlElement('h2', null, Text::create(t('Event Info'))),
            $comment->object_type === 'host'
                ? new HorizontalKeyValue(t('Host'), HtmlElement::create(
                    'span',
                    ['class' => 'accompanying-text'],
                    HtmlElement::create('span', ['class' => 'subject'], $this->event->host->display_name)
                ))
                : new HorizontalKeyValue(t('Service'), HtmlElement::create(
                    'span',
                    ['class' => 'accompanying-text'],
                    FormattedString::create(
                        t('%s on %s', '<service> on <host>'),
                        HtmlElement::create('span', ['class' => 'subject'], $this->event->service->display_name),
                        HtmlElement::create('span', ['class' => 'subject'], $this->event->host->display_name)
                    )
                )),
            new HorizontalKeyValue(t('Entered On'), DateFormatter::formatDateTime($comment->entry_time)),
            new HorizontalKeyValue(t('Author'), [new Icon('user'), $comment->author]),
            new HorizontalKeyValue(t('Expires On'), $comment->expire_time
                ? DateFormatter::formatDateTime($comment->expire_time)
                : '-')
        );

        if ($comment->entry_type === 'ack') {
            $this->addHtml(
                new HtmlElement('h2', null, Text::create(t('This comment is tied to an acknowledgement'))),
                new HorizontalKeyValue(t('Sticky'), $comment->is_sticky ? t('Yes') : t('No')),
                new HorizontalKeyValue(t('Persistent'), $comment->is_persistent ? t('Yes') : t('No'))
            );
        }

        if ($comment->has_been_removed) {
            $this->addHtml(new HtmlElement('h2', null, Text::create(t('This comment has been removed'))));
            if ($comment->removed_by) {
                $this->addHtml(
                    new HorizontalKeyValue(t('Removed On'), DateFormatter::formatDateTime($comment->remove_time))
                );
                if ($comment->removed_by) {
                    $this->addHtml(
                        new HorizontalKeyValue(t('Removed by'), [new Icon('user'), $comment->removed_by])
                    );
                }
            } else {
                $this->addHtml(
                    new HorizontalKeyValue(t('Expired On'), DateFormatter::formatDateTime($comment->remove_time))
                );
            }
        }
    }

    protected function assembleFlappingEvent(FlappingHistory $flapping)
    {
        $this->addHtml(
            new HtmlElement('h2', null, Text::create(t('Event Info'))),
            $flapping->object_type === 'host'
                ? new HorizontalKeyValue(t('Host'), HtmlElement::create(
                    'span',
                    ['class' => 'accompanying-text'],
                    HtmlElement::create('span', ['class' => 'subject'], $this->event->host->display_name)
                ))
                : new HorizontalKeyValue(t('Service'), HtmlElement::create(
                    'span',
                    ['class' => 'accompanying-text'],
                    FormattedString::create(
                        t('%s on %s', '<service> on <host>'),
                        HtmlElement::create('span', ['class' => 'subject'], $this->event->service->display_name),
                        HtmlElement::create('span', ['class' => 'subject'], $this->event->host->display_name)
                    )
                )),
            new HorizontalKeyValue(t('Started on'), DateFormatter::formatDateTime($flapping->start_time))
        );
        if ($this->event->event_type === 'flapping_start') {
            $this->addHtml(new HorizontalKeyValue(t('Reason'), sprintf(
                t('State change rate of %.2f%% exceeded the threshold (%.2f%%)'),
                $flapping->percent_state_change_start,
                $flapping->flapping_threshold_high
            )));
        } else {
            $this->addHtml(
                new HorizontalKeyValue(t('Ended on'), DateFormatter::formatDateTime($flapping->end_time)),
                new HorizontalKeyValue(t('Reason'), sprintf(
                    t('State change rate of %.2f%% undercut the threshold (%.2f%%)'),
                    $flapping->percent_state_change_end,
                    $flapping->flapping_threshold_low
                ))
            );
        }
    }

    protected function assembleAcknowledgeEvent(AcknowledgementHistory $acknowledgement)
    {
        if ($acknowledgement->comment) {
            $this->addHtml(
                new HtmlElement('h2', null, Text::create(t('Comment'))),
                new MarkdownText($acknowledgement->comment)
            );
        }

        $this->addHtml(
            new HtmlElement('h2', null, Text::create(t('Event Info'))),
            new HorizontalKeyValue(t('Set on'), DateFormatter::formatDateTime($acknowledgement->set_time)),
            new HorizontalKeyValue(t('Author'), [new Icon('user'), $acknowledgement->author]),
            $acknowledgement->object_type === 'host'
                ? new HorizontalKeyValue(t('Host'), HtmlElement::create(
                    'span',
                    ['class' => 'accompanying-text'],
                    HtmlElement::create('span', ['class' => 'subject'], $this->event->host->display_name)
                ))
                : new HorizontalKeyValue(t('Service'), HtmlElement::create(
                    'span',
                    ['class' => 'accompanying-text'],
                    FormattedString::create(
                        t('%s on %s', '<service> on <host>'),
                        HtmlElement::create('span', ['class' => 'subject'], $this->event->service->display_name),
                        HtmlElement::create('span', ['class' => 'subject'], $this->event->host->display_name)
                    )
                ))
        );

        if ($this->event->event_type === 'ack_set') {
            $this->addHtml(
                new HorizontalKeyValue(t('Expires On'), $acknowledgement->expire_time
                    ? DateFormatter::formatDateTime($acknowledgement->expire_time)
                    : '-'),
                new HorizontalKeyValue(t('Sticky'), $acknowledgement->is_sticky ? t('Yes') : t('No')),
                new HorizontalKeyValue(t('Persistent'), $acknowledgement->is_persistent ? t('Yes') : t('No'))
            );
        } else {
            $this->addHtml(new HorizontalKeyValue(t('Cleared on'), DateFormatter::formatDateTime(
                $acknowledgement->clear_time ?: $this->event->event_time
            )));
            if ($acknowledgement->cleared_by) {
                $this->addHtml(
                    new HorizontalKeyValue(t('Cleared by'), [new Icon('user', $acknowledgement->cleared_by)])
                );
            } else {
                $expired = false;
                if ($acknowledgement->expire_time) {
                    $now = (new DateTime())->setTimezone(new DateTimeZone(DateTimeZone::UTC));
                    $expiresOn = clone $now;
                    $expiresOn->setTimestamp($acknowledgement->expire_time);
                    if ($now <= $expiresOn) {
                        $expired = true;
                        $this->addHtml(new HorizontalKeyValue(t('Removal Reason'), t(
                            'The acknowledgement expired on %s',
                            DateFormatter::formatDateTime($acknowledgement->expire_time)
                        )));
                    }
                }

                if (! $expired) {
                    if ($acknowledgement->is_sticky) {
                        $this->addHtml(
                            new HorizontalKeyValue(
                                t('Reason'),
                                $acknowledgement->object_type === 'host'
                                    ? t('Host recovered')
                                    : t('Service recovered')
                            )
                        );
                    } else {
                        $this->addHtml(
                            new HorizontalKeyValue(
                                t('Reason'),
                                $acknowledgement->object_type === 'host'
                                    ? t('Host recovered') // Hosts have no other state between UP and DOWN
                                    : t('Service changed its state')
                            )
                        );
                    }
                }
            }
        }
    }

    protected function assemble()
    {
        switch ($this->event->event_type) {
            case 'notification':
                $this->assembleNotificationEvent($this->event->notification);

                break;
            case 'state_change':
                $this->assembleStateChangeEvent($this->event->state);

                break;
            case 'downtime_start':
            case 'downtime_end':
                $this->assembleDowntimeEvent($this->event->downtime);

                break;
            case 'comment_add':
            case 'comment_remove':
                $this->assembleCommentEvent($this->event->comment);

                break;
            case 'flapping_start':
            case 'flapping_end':
                $this->assembleFlappingEvent($this->event->flapping);

                break;
            case 'ack_set':
            case 'ack_clear':
                $this->assembleAcknowledgeEvent($this->event->acknowledgement);

                break;
        }
    }
}
