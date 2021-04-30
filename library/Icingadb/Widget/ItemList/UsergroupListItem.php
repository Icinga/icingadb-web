<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\BaseTableRowItem;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Model\Usergroup;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Link;

/**
 * Usergroup item of a usergroup list. Represents one database row.
 *
 * @property Usergroup $item
 * @property UsergroupList $list
 */
class UsergroupListItem extends BaseTableRowItem
{
    use NoSubjectLink;

    protected function init()
    {
        $this->setNoSubjectLink($this->list->getNoSubjectLink());
    }

    protected function assembleVisual(BaseHtmlElement $visual)
    {
        $visual->add(new HtmlElement('div', ['class' => 'usergroup-ball'], $this->item->display_name[0]));
    }

    protected function assembleTitle(BaseHtmlElement $title)
    {
        $title->add([
            $this->getNoSubjectLink()
                ? new HtmlElement('span', ['class' => 'subject'], $this->item->display_name)
                : new Link($this->item->display_name, Links::usergroup($this->item), ['class' => 'subject']),
            new HtmlElement('br'),
            $this->item->name
        ]);
    }

    protected function assembleColumns(HtmlDocument $columns)
    {
    }
}
