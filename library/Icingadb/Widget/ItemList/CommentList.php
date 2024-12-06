<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\DetailActions;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\ObjectLinkDisabled;
use Icinga\Module\Icingadb\Common\TicketLinks;
use Icinga\Module\Icingadb\Common\ViewMode;
use ipl\Web\Common\BaseItemList;
use ipl\Web\Url;

class CommentList extends BaseItemList
{
    use ObjectLinkDisabled;
    use ViewMode;
    use TicketLinks;
    use DetailActions;

    protected $defaultAttributes = ['class' => 'comment-list'];

    protected function getItemClass(): string
    {
        $viewMode = $this->getViewMode();

        $this->addAttributes(['class' => $viewMode]);

        if ($viewMode === 'minimal') {
            return CommentListItemMinimal::class;
        } elseif ($viewMode === 'detailed') {
            $this->removeAttribute('class', 'default-layout');
        }

        return CommentListItem::class;
    }

    protected function init(): void
    {
        $this->initializeDetailActions();
        $this->setMultiselectUrl(Links::commentsDetails());
        $this->setDetailUrl(Url::fromPath('icingadb/comment'));
    }
}
