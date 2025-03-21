<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Exception\NotImplementedError;
use Icinga\Module\Icingadb\Model\Comment;
use Icinga\Module\Icingadb\View\CommentRenderer;
use ipl\Orm\Model;
use ipl\Web\Widget\ItemList;

/**
 * TicketLinkObjectList
 *
 * Create a list of icingadb objects with ticket links
 *
 * @extends ObjectList //TODO: define object type
 */
class TicketLinkObjectList extends ObjectList
{
    public function __construct($data)
    {
        ItemList::__construct($data, function (Model $item) {
            if ($item instanceof Comment) {
                return (new CommentRenderer())->setIsDetailView();
            }

            throw new NotImplementedError('Not implemented');
        });
    }
}
