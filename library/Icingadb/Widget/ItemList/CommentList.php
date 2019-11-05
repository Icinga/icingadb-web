<?php

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Widget\BaseItemList;

class CommentList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'comment-list'];

    protected function getItemClass()
    {
        return CommentListItem::class;
    }
}
