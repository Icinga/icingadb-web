<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\HostStates;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Common\ServiceStates;
use Icinga\Module\Icingadb\Compat\CompatPluginOutput;
use Icinga\Module\Icingadb\Widget\CommonListItem;
use Icinga\Module\Icingadb\Widget\TimeAgo;
use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;

class NotificationListItem extends CommonListItem
{
    use HostLink;
    use ServiceLink;

    /**
     * Get a localized phrase for the given notification type
     *
     * @param string $type
     *
     * @return string
     */
    public static function phraseForType($type)
    {
        switch ($type) {
            case 'acknowledgement':
                return t('Problem was acknowledged');
            case 'custom':
                return t('Custom Notification was triggered');
            case 'downtime_end':
                return t('Downtime ended');
            case 'downtime_removed':
                return t('Downtime was removed');
            case 'downtime_start':
                return t('Downtime was started');
            case 'flapping_end':
                return t('Flapping ended');
            case 'flapping_start':
                return t('Flapping detected');
            case 'problem':
                return t('%s ran into a problem');
            case 'recovery':
                return t('%s recovered');
            default:
                throw new InvalidArgumentException(sprintf('Type %s is not a valid notification type', $type));
        }
    }

    protected function assembleCaption(BaseHtmlElement $caption)
    {
        if (in_array($this->item->type, ['flapping_end', 'flapping_start', 'problem', 'recovery'])) {
            $caption->add(CompatPluginOutput::getInstance()->render($this->item->text));
        } else {
            $caption->add([
                new Icon(Icons::USER),
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
            case 'custom':
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
            sprintf(self::phraseForType($this->item->type), ucfirst($this->item->object_type)),
            Html::tag('br')
        ]);

        if ($this->item->object_type === 'host') {
            $link = $this->createHostLink($this->item->host, true);
        } else {
            $link = $this->createServiceLink($this->item->service, $this->item->host, true);
        }

        $title->add($link);
    }

    protected function createTimestamp()
    {
        return new TimeAgo($this->item->send_time);
    }
}
