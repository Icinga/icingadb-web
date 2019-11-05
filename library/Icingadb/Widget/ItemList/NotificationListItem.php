<?php

namespace Icinga\Module\Eagle\Widget\ItemList;

use Icinga\Module\Eagle\Common\HostLink;
use Icinga\Module\Eagle\Common\HostStates;
use Icinga\Module\Eagle\Common\Icons;
use Icinga\Module\Eagle\Common\ServiceLink;
use Icinga\Module\Eagle\Common\ServiceStates;
use Icinga\Module\Eagle\Widget\CommonListItem;
use Icinga\Module\Eagle\Widget\TimeAgo;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;

class NotificationListItem extends CommonListItem
{
    use HostLink;
    use ServiceLink;

    const PHRASES = [
        'acknowledgement'  => 'Problem was acknowledged',
        'custom'           => 'Custom Notification was triggered',
        'downtime_end'     => 'Downtime ended',
        'downtime_removed' => 'Downtime was removed',
        'downtime_start'   => 'Downtime was started',
        'flapping_end'     => 'Flapping ended',
        'flapping_start'   => 'Flapping detected',
        'problem'          => '%s ran into a problem',
        'recovery'         => '%s recovered'
    ];

    protected function assembleCaption(BaseHtmlElement $caption)
    {
        if (in_array($this->item->type, ['flapping_end', 'flapping_start', 'problem', 'recovery'])) {
            $caption->addAttributes(['class' => 'plugin-output']);
            $caption->add($this->item->text);
        } else {
            $caption->add([
                New Icon(Icons::USER),
                $this->item->author,
                ': ',
                $this->item->text
            ]);
        }
    }

    protected function assembleVisual(BaseHtmlElement $visual)
    {
        switch ($this->item->type) {
            case 'acknowledgement':
                $visual->add(
                    Html::tag('div', ['class' => 'icon-ball ball-size-xl'], new Icon(Icons::IS_ACKNOWLEDGED))
                );

                break;
            case 'Custom':
                $visual->add(
                    Html::tag('div', ['class' => 'icon-ball ball-size-xl'], new Icon(Icons::NOTIFICATION))
                );

                break;
            case 'downtime_end':
            case 'downtime_removed':
            case 'downtime_start':
                $visual->add(
                    Html::tag('div', ['class' => 'icon-ball ball-size-xl'], new Icon(Icons::IN_DOWNTIME))
                );

                break;
            case 'flapping_end':
            case 'flapping_start':
                $visual->add(
                    Html::tag('div', ['class' => 'icon-ball ball-size-xl'], new Icon(Icons::IS_FLAPPING))
                );

                break;
            case 'problem':
            case 'recovery':
                if ($this->item->object_type === 'host') {
                    $state = HostStates::text($this->item->state);
                    $previousHardState = HostStates::text($this->item->previous_hard_state);
                } else {
                    $state = ServiceStates::text($this->item->state);
                    $previousHardState = ServiceStates::text($this->item->previous_hard_state);
                }

                $visual->add([
                    new StateBall($previousHardState, StateBall::SIZE_BIG),
                    new StateBall($state, StateBall::SIZE_BIG)
                ]);

                break;
        }
    }

    protected function assembleTitle(BaseHtmlElement $title)
    {
        $title->add([
            sprintf(self::PHRASES[$this->item->type], ucfirst($this->item->object_type)),
            Html::tag('br')
        ]);

        if ($this->item->object_type === 'host') {
            $link = $this->createHostLink($this->item->host, true);
        } else {
            $link = $this->createServiceLink($this->item->service, $this->item->service->host, true);
        }

        $title->add($link);
    }

    protected function createTimestamp()
    {
        return new TimeAgo($this->item->event_time);
    }
}
