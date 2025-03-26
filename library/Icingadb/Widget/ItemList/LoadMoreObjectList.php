<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Exception\NotImplementedError;
use Icinga\Module\Icingadb\Common\LoadMore;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Model\NotificationHistory;
use Icinga\Module\Icingadb\View\EventRenderer;
use Icinga\Module\Icingadb\View\NotificationRenderer;
use ipl\Orm\Model;
use ipl\Orm\ResultSet;
use ipl\Web\Widget\ItemList;

/**
 * LoadMoreObjectList
 *
 * Create a list of icingadb objects with Load more link
 *
 * @template Item of NotificationHistory|History
 *
 * @extends ObjectList<Item>
 */
class LoadMoreObjectList extends ObjectList
{
    use LoadMore;

    public function __construct(ResultSet $data)
    {
        ItemList::__construct($data, function (Model $item) {
            if ($item instanceof NotificationHistory) {
                return new NotificationRenderer();
            } elseif ($item instanceof History) {
                return new EventRenderer();
            }

            throw new NotImplementedError('Not implemented');
        });

        $this->data = $this->getIterator($data);
    }
}
