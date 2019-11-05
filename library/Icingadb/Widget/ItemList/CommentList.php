<?php

namespace Icinga\Module\Eagle\Widget\ItemList;

use Icinga\Module\Eagle\Widget\BaseItemList;

class CommentList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'comment-list'];

    protected function getItemClass()
    {
        return CommentListItem::class;
    }
}
