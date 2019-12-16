<?php

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\CaptionDisabled;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Widget\BaseItemList;

class CommentList extends BaseItemList
{
    use CaptionDisabled;

    protected $defaultAttributes = ['class' => 'comment-list'];

    protected function getItemClass()
    {
        return CommentListItem::class;
    }

    protected function init()
    {
        $this->setMultiselectUrl(Links::commentsDetails());
    }
}
