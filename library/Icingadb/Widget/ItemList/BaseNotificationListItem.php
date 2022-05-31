<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\HostStates;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Common\ServiceStates;
use Icinga\Module\Icingadb\Util\PluginOutput;
use Icinga\Module\Icingadb\Common\BaseListItem;
use Icinga\Module\Icingadb\Widget\PluginOutputContainer;
use Icinga\Module\Icingadb\Widget\StateChange;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\TimeAgo;
use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

abstract class BaseNotificationListItem extends BaseListItem
{
    use HostLink;
    use NoSubjectLink;
    use ServiceLink;

    /** @var NotificationList */
    protected $list;

    protected function init()
    {
        $this->setNoSubjectLink($this->list->getNoSubjectLink());
        $this->list->addDetailFilterAttribute($this, Filter::equal('id', bin2hex($this->item->history->id)));
    }

    /**
     * Get a localized phrase for the given notification type
     *
     * @param string $type
     *
     * @return string
     */
    public static function phraseForType(string $type): string
    {
        switch ($type) {
            case 'acknowledgement':
                return t('Problem acknowledged');
            case 'custom':
                return t('Custom Notification triggered');
            case 'downtime_end':
                return t('Downtime ended');
            case 'downtime_removed':
                return t('Downtime removed');
            case 'downtime_start':
                return t('Downtime started');
            case 'flapping_end':
                return t('Flapping stopped');
            case 'flapping_start':
                return t('Flapping started');
            case 'problem':
                return t('%s ran into a problem');
            case 'recovery':
                return t('%s recovered');
            default:
                throw new InvalidArgumentException(sprintf('Type %s is not a valid notification type', $type));
        }
    }

    abstract protected function getStateBallSize();

    protected function assembleCaption(BaseHtmlElement $caption)
    {
        if (in_array($this->item->type, ['flapping_end', 'flapping_start', 'problem', 'recovery'])) {
            $caption->addHtml(new PluginOutputContainer(
                (new PluginOutput($this->item->text))
                    ->setCommandName($this->item->object_type === 'host'
                        ? $this->item->host->checkcommand_name
                        : $this->item->service->checkcommand_name)
            ));
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
                $visual->addHtml(HtmlElement::create(
                    'div',
                    ['class' => ['icon-ball', 'ball-size-' . $this->getStateBallSize()]],
                    new Icon(Icons::IS_ACKNOWLEDGED)
                ));

                break;
            case 'custom':
                $visual->addHtml(HtmlElement::create(
                    'div',
                    ['class' => ['icon-ball', 'ball-size-' . $this->getStateBallSize()]],
                    new Icon(Icons::NOTIFICATION)
                ));

                break;
            case 'downtime_end':
            case 'downtime_removed':
            case 'downtime_start':
                $visual->addHtml(HtmlElement::create(
                    'div',
                    ['class' => ['icon-ball', 'ball-size-' . $this->getStateBallSize()]],
                    new Icon(Icons::IN_DOWNTIME)
                ));

                break;
            case 'flapping_end':
            case 'flapping_start':
                $visual->addHtml(HtmlElement::create(
                    'div',
                    ['class' => ['icon-ball', 'ball-size-' . $this->getStateBallSize()]],
                    new Icon(Icons::IS_FLAPPING)
                ));

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

                $visual->addHtml(new StateChange($state, $previousHardState));

                break;
        }
    }

    protected function assembleTitle(BaseHtmlElement $title)
    {
        if ($this->getNoSubjectLink()) {
            $title->addHtml(HtmlElement::create(
                'span',
                ['class' => 'subject'],
                sprintf(self::phraseForType($this->item->type), ucfirst($this->item->object_type))
            ));
        } else {
            $title->addHtml(new Link(
                sprintf(self::phraseForType($this->item->type), ucfirst($this->item->object_type)),
                Links::event($this->item->history),
                ['class' => 'subject']
            ));
        }

        if ($this->item->object_type === 'host') {
            $link = $this->createHostLink($this->item->host, true);
        } else {
            $link = $this->createServiceLink($this->item->service, $this->item->host, true);
        }

        $title->addHtml(Text::create(' '), $link);
    }

    protected function createTimestamp()
    {
        return new TimeAgo($this->item->send_time);
    }
}
