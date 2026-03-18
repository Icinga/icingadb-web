<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Exception\NotImplementedError;
use Icinga\Module\Icingadb\Common\LoadMore;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Model\NotificationHistory;
use Icinga\Module\Icingadb\View\EventRenderer;
use Icinga\Module\Icingadb\View\NotificationRenderer;
use IntlDateFormatter;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Orm\Model;
use ipl\Orm\ResultSet;
use ipl\Web\Widget\ItemList;
use Locale;

/**
 * HistoryObjectList
 *
 * Create a list of History or NotificationHistory objects with a Load more link
 * and add a separator when a new day begins.
 *
 * @template Item of NotificationHistory|History
 *
 * @extends ObjectList<Item>
 */
class HistoryObjectList extends ObjectList
{
    use LoadMore;

    /** @var ?int The timestamp of the previous element in the list */
    protected ?int $previousTimeStamp = null;

    /**
     * A list of History or NotificationHistory objects with a Load more link and add a separator between days.
     *
     * @param ResultSet $data
     * @param ?int $previousTimeStamp
     * @param bool $useRelativeTimestamps
     */
    public function __construct(ResultSet $data, ?int $previousTimeStamp = null, bool $useRelativeTimestamps = false)
    {
        ItemList::__construct($data, function (Model $item) use ($useRelativeTimestamps) {
            if ($item instanceof NotificationHistory) {
                return new NotificationRenderer($useRelativeTimestamps);
            } elseif ($item instanceof History) {
                return new EventRenderer($useRelativeTimestamps);
            }

            throw new NotImplementedError('Not implemented');
        });

        $this->data = $this->getIterator($data);
        $this->previousTimeStamp = $previousTimeStamp;
    }

    protected function init(): void
    {
        $formatter = new IntlDateFormatter(
            Locale::getDefault(),
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::NONE
        );

        $this->on(self::BEFORE_ITEM_ADD, function ($item, $data) use ($formatter) {
            if ($data instanceof NotificationHistory) {
                $timestamp = $data->send_time->getTimestamp();
            } else {
                $timestamp = $data->event_time->getTimestamp();
            }

            if (
                $this->previousTimeStamp === null
                || $formatter->format($this->previousTimeStamp) !== $formatter->format($timestamp)
            ) {
                $this->addHtml(new HtmlElement(
                    'li',
                    new Attributes(['class' => ['day-separator']]),
                    new Text($formatter->format($timestamp))
                ));
            }

            $this->previousTimeStamp = $timestamp;
        });

        $this->on(self::ON_ASSEMBLED, fn () => $this->loadMoreUrl->setParam('last-entry', $this->previousTimeStamp));
        parent::init();
    }
}
