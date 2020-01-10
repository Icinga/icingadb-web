<?php

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\ListItemMinimalLayout;

class CommentListItemMinimal extends BaseCommentListItem
{
    use ListItemMinimalLayout;

    protected function init()
    {
        parent::init();

        if ($this->list->isCaptionDisabled()) {
            $this->setCaptionDisabled();
        }
    }
}
