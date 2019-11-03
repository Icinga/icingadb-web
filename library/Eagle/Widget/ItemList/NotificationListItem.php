<?php

namespace Icinga\Module\Eagle\Widget\ItemList;

use Icinga\Date\DateFormatter;
use Icinga\Module\Eagle\Common\HostLink;
use Icinga\Module\Eagle\Common\HostStates;
use Icinga\Module\Eagle\Common\Icons;
use Icinga\Module\Eagle\Common\ServiceLink;
use Icinga\Module\Eagle\Common\ServiceStates;
use Icinga\Module\Eagle\Widget\CommonListItem;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;

class NotificationListItem extends CommonListItem
{
    use HostLink;
    use ServiceLink;

    const PHRASES = [
        'Ack'            => 'Problem was acknowledged',
        'Custom'         => 'Custom Notification was triggered',
        'DowntimeEnd'    => 'Downtime ended',
        'DowntimeRemove' => 'Downtime was removed',
        'DowntimeStart'  => 'Downtime was started',
        'FlappingEnd'    => 'Flapping ended',
        'FlappingStart'  => 'Flapping detected',
        'Problem'        => '%s ran into a problem',
        'Recovery'       => '%s recovered'
    ];

    const TYPES = [
        1   => 'DowntimeStart',
        2   => 'DowntimeEnd',
        4   => 'DowntimeRemove',
        8   => 'Custom',
        16  => 'Ack',
        32  => 'Problem',
        64  => 'Recovery',
        128 => 'FlappingStart',
        256 => 'FlappingEnd'
    ];

    protected function assembleCaption(BaseHtmlElement $caption)
    {
        if (in_array(self::TYPES[$this->item->type], ['FlappingStart', 'FlappingEnd', 'Problem', 'Recovery'])) {
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
        switch (self::TYPES[$this->item->type]) {
            case 'Ack':
                $visual->add(Html::tag('div', ['class' => 'icon-ball ball-size-xl'], new Icon(Icons::IS_ACKNOWLEDGED)));

                break;
            case 'Custom':
                $visual->add(Html::tag('div', ['class' => 'icon-ball ball-size-xl'], new Icon(Icons::NOTIFICATION)));

                break;
            case 'DowntimeEnd':
            case 'DowntimeRemove':
            case 'DowntimeStart':
                $visual->add(Html::tag('div', ['class' => 'icon-ball ball-size-xl'], new Icon(Icons::IN_DOWNTIME)));

                break;
            case 'FlappingEnd':
            case 'FlappingStart':
                $visual->add(Html::tag('div', ['class' => 'icon-ball ball-size-xl'], new Icon(Icons::IS_FLAPPING)));

                break;
            case 'Problem':
            case 'Recovery':
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
            sprintf(self::PHRASES[self::TYPES[$this->item->type]], ucfirst($this->item->object_type)),
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
        return Dateformatter::timeAgo($this->item->send_time);
    }
}
