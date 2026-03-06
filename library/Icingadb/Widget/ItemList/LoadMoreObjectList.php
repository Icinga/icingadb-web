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
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\ItemList;
use Locale;

/**
 * LoadMoreObjectList
 *
 * Create a list of History or NotificationHistory objects with a Load more link
 * and add a separator when a new day begins.
 *
 * @template Item of NotificationHistory|History
 *
 * @extends ObjectList<Item>
 */
class LoadMoreObjectList extends ObjectList
{
    use LoadMore;

    /** @var ?int The timestamp of the previous element in the list */
    protected ?int $previousTimeStamp = null;

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

    /**
     * Add a separator with the next date when a new day begins in the history.
     * The timestamp of the last entry is added to the loadMoreURl to make this possible.
     *
     * @return void
     */
    protected function assemble(): void
    {
        $formatter = new IntlDateFormatter(
            Locale::getDefault(),
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            'UTC',
            IntlDateFormatter::GREGORIAN,
            'MMM d, YYYY'
        );

        $addDaySeparator = function ($previousDate, $date) use ($formatter) {
            if (! $previousDate || $formatter->format($previousDate) !== $formatter->format($date)) {
                return new HtmlElement(
                    'li',
                    new Attributes(['class' => ['day-separator']]),
                    new Text($formatter->format($date))
                );
            }

            return null;
        };

        foreach ($this->data as $data) {
            if (! $data instanceof NotificationHistory) {
                $daySeparator = $addDaySeparator($this->previousTimeStamp, $data->event_time->getTimestamp());
                $this->previousTimeStamp = $data->event_time->getTimestamp();
            } else {
                $daySeparator = $addDaySeparator($this->previousTimeStamp, $data->send_time->getTimestamp());
                $this->previousTimeStamp = $data->history->event_time->getTimestamp();
            }

            if ($daySeparator !== null) {
                $this->addHtml($daySeparator);
            }

            $item = $this->createListItem($data);
            $this->emit(self::BEFORE_ITEM_ADD, [$item, $data]);
            $this->addHtml($item);
            $this->emit(self::ON_ITEM_ADD, [$item, $data]);
        }

        $this->loadMoreUrl->setParam('last-entry', $this->previousTimeStamp);

        if ($this->isEmpty()) {
            $this->setTag('div');
            $this->addHtml(new EmptyState($this->getEmptyStateMessage()));
        }
    }
}
