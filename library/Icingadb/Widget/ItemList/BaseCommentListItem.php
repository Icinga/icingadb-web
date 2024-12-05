<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Widget\Detail\CommentHeaderUtils;
use Icinga\Module\Icingadb\Model\Comment;
use ipl\Web\Common\BaseListItem;
use ipl\Stdlib\Filter;

/**
 * Comment item of a comment list. Represents one database row.
 *
 * @property Comment $item
 * @property CommentList $list
 */
abstract class BaseCommentListItem extends BaseListItem
{
    use CommentHeaderUtils;

    protected function getObject(): Comment
    {
        return $this->item;
    }

    protected function wantSubjectLink(): bool
    {
        return ! $this->list->getNoSubjectLink();
    }

    protected function wantObjectLink(): bool
    {
        return ! $this->list->getObjectLinkDisabled();
    }

    protected function init(): void
    {
        $this->setTicketLinkEnabled($this->list->getTicketLinkEnabled());
        $this->list->addDetailFilterAttribute($this, Filter::equal('name', $this->item->name));
        $this->list->addMultiselectFilterAttribute($this, Filter::equal('name', $this->item->name));
    }
}
