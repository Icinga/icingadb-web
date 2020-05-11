<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Date\DateFormatter;
use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\HostStates;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Common\ServiceStates;
use Icinga\Module\Icingadb\Compat\CompatPluginOutput;
use Icinga\Module\Icingadb\Widget\CheckAttempt;
use Icinga\Module\Icingadb\Widget\CommonListItem;
use Icinga\Module\Icingadb\Widget\StateChange;
use Icinga\Module\Icingadb\Widget\TimeAgo;
use Icinga\Web\Helper\Markdown;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use ipl\Web\Widget\Icon;

class HistoryListItem extends CommonListItem
{
    use HostLink;
    use ServiceLink;

    protected function assembleCaption(BaseHtmlElement $caption)
    {
        switch ($this->item->event_type) {
            case 'comment_add':
            case 'comment_remove':
                $caption->add([
                    new Icon(Icons::USER),
                    $this->item->comment->author,
                    ': ',
                    HtmlString::create(Markdown::line($this->item->comment->comment))
                ]);

                break;
            case 'downtime_end':
            case 'downtime_start':
                $caption->add([
                    new Icon(Icons::USER),
                    $this->item->downtime->author,
                    ': ',
                    HtmlString::create(Markdown::line($this->item->downtime->comment))
                ]);

                break;
            case 'flapping_start':
                $caption
                    ->add(sprintf(
                        t('State Change Rate: %.2f%%; Start Threshold: %.2f%%'),
                        $this->item->flapping->percent_state_change_start,
                        $this->item->host->flapping_threshold_high
                    ))
                    ->getAttributes()
                    ->add('class', 'plugin-output');

                break;
            case 'flapping_end':
                $caption
                    ->add(sprintf(
                        t('State Change Rate: %.2f%%; End Threshold: %.2f%%; Flapping for %s'),
                        $this->item->host->flapping_threshold_low,
                        $this->item->host->flapping_threshold_high,
                        DateFormatter::formatDuration(
                            $this->item->flapping->end_time - $this->item->flapping->start_time
                        )
                    ))
                    ->getAttributes()
                    ->add('class', 'plugin-output');

                break;
            case 'ack_clear':
                if (! empty($this->item->acknowledgement->cleared_by)) {
                    $caption->add([
                        new Icon(Icons::USER),
                        sprintf(
                            t('Cleared by: %s', '..<username>'),
                            $this->item->acknowledgement->cleared_by
                        )
                    ]);

                    break;
                }

                // Fallthrough
            case 'ack_set':
                $caption->add([
                    new Icon(Icons::USER),
                    $this->item->acknowledgement->author,
                    ': ',
                    HtmlString::create(Markdown::line($this->item->acknowledgement->comment))
                ]);

                break;
            case 'notification':
                if (! empty($this->item->notification->author)) {
                    $caption->add([
                        new Icon(Icons::USER),
                        $this->item->notification->author,
                        ': ',
                        $this->item->notification->text
                    ]);
                } else {
                    $caption->add(CompatPluginOutput::getInstance()->render($this->item->notification->text));
                }

                break;
            case 'state_change':
                $caption->add(CompatPluginOutput::getInstance()->render($this->item->state->output));

                break;
        }
    }

    protected function assembleVisual(BaseHtmlElement $visual)
    {
        switch ($this->item->event_type) {
            case 'comment_add':
                $visual->add(
                    Html::tag('div', ['class' => 'icon-ball ball-size-xl'], new Icon(Icons::COMMENT))
                );

                break;
            case 'comment_remove':
            case 'downtime_end':
            case 'ack_clear':
                $visual->add(
                    Html::tag('div', ['class' => 'icon-ball ball-size-xl'], new Icon(Icons::REMOVE))
                );

                break;
            case 'downtime_start':
                $visual->add(
                    Html::tag('div', ['class' => 'icon-ball ball-size-xl'], new Icon(Icons::IN_DOWNTIME))
                );

                break;
            case 'ack_set':
                $visual->add(
                    Html::tag('div', ['class' => 'icon-ball ball-size-xl'], new Icon(Icons::IS_ACKNOWLEDGED))
                );

                break;
            case 'flapping_end':
            case 'flapping_start':
                $visual->add(
                    Html::tag('div', ['class' => 'icon-ball ball-size-xl'], new Icon(Icons::IS_FLAPPING))
                );

                break;
            case 'notification':
                $visual->add(
                    Html::tag('div', ['class' => 'icon-ball ball-size-xl'], new Icon(Icons::NOTIFICATION))
                );

                break;
            case 'state_change':
                $previousState = 'previous_soft_state';

                if ($this->item->state->state_type === 'soft') {
                    $state = 'soft_state';

                    $visual->add(new CheckAttempt($this->item->state->attempt, $this->item->state->max_check_attempts));
                } else {
                    $state = 'hard_state';
                }

                if ($this->item->object_type === 'host') {
                    $state = HostStates::text($this->item->state->$state);
                    $previousHardState = HostStates::text($this->item->state->$previousState);
                } else {
                    $state = ServiceStates::text($this->item->state->$state);
                    $previousHardState = ServiceStates::text($this->item->state->$previousState);
                }

                $visual->prepend(new StateChange($state, $previousHardState));

                break;
        }
    }

    protected function assembleTitle(BaseHtmlElement $title)
    {
        switch ($this->item->event_type) {
            case 'comment_add':
                $title->add(t('Comment added'));

                break;
            case 'comment_remove':
                $title->add(t('Comment removed'));

                break;
            case 'downtime_end':
                $title->add(t('Downtime ended'));

                break;
            case 'downtime_start':
                $title->add(t('Downtime started'));

                break;
            case 'flapping_start':
                $title->add(t('Flapping started'));

                break;
            case 'flapping_end':
                $title->add(t('Flapping stopped'));

                break;
            case 'ack_set':
                $title->add(t('Acknowledgement set'));

                break;
            case 'ack_clear':
                $title->add(t('Acknowledgement cleared'));

                break;
            case 'notification':
                $title->add([
                    t('Notification'),
                    ': ',
                    sprintf(
                        NotificationListItem::phraseForType($this->item->notification->type),
                        ucfirst($this->item->object_type)
                    )
                ]);

                break;
            case 'state_change':
                $state = $this->item->state === 'hard' ? $this->item->state->hard_state : $this->item->state->soft_state;
                if ($state === 0) {
                    $title->add(ucfirst($this->item->object_type) . t(' recovered'));
                } else {
                    if ($this->item->state->state_type === 'hard') {
                    	$title->add(t('Hard state changed'));
                	} else {
                    	$title->add(t('Soft state changed'));
                	}
				}

                break;
            default:
                $title->add($this->item->event_type);

                break;
        }

        if ($this->item->object_type === 'host') {
            $link = $this->createHostLink($this->item->host, true);
        } else {
            $link = $this->createServiceLink($this->item->service, $this->item->host, true);
        }

        $title->add([Html::tag('br'), $link]);
    }

    protected function createTimestamp()
    {
        return new TimeAgo($this->item->event_time);
    }
}
