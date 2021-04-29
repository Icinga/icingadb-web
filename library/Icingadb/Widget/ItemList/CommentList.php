<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\CaptionDisabled;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Common\ObjectLinkDisabled;
use Icinga\Module\Icingadb\Common\ViewMode;
use Icinga\Module\Icingadb\Widget\BaseItemList;
use ipl\Web\Url;

class CommentList extends BaseItemList
{
    use CaptionDisabled;
    use NoSubjectLink;
    use ObjectLinkDisabled;
    use ViewMode;

    protected $defaultAttributes = ['class' => 'comment-list'];

    protected function getItemClass()
    {
        $viewMode = $this->getViewMode();

        $this->addAttributes(['class' => $viewMode]);

        if ($viewMode === 'minimal') {
            return CommentListItemMinimal::class;
        }

        return CommentListItem::class;
    }

    protected function init()
    {
        $this->setMultiselectUrl(Links::commentsDetails());
        $this->setDetailUrl(Url::fromPath('icingadb/comment'));
    }
}
