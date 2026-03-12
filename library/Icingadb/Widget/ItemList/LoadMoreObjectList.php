<?php

// SPDX-FileCopyrightText: 2025 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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

    public function __construct(
        ResultSet $data,
        ?int $previousTimeStamp = null,
        bool $useRelativeTimestamps = false,
        bool $interactiveTimestamps = true
    ) {
        ItemList::__construct($data, function (Model $item) use ($useRelativeTimestamps, $interactiveTimestamps) {
            if ($item instanceof NotificationHistory) {
                return new NotificationRenderer($useRelativeTimestamps, $interactiveTimestamps);
            } elseif ($item instanceof History) {
                return new EventRenderer($useRelativeTimestamps, $interactiveTimestamps);
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

        parent::assemble();
        $this->loadMoreUrl->setParam('last-entry', $this->previousTimeStamp);
    }
}
