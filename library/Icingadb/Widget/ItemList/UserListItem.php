<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\BaseTableRowItem;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Model\User;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Link;

/**
 * User item of a user list. Represents one database row.
 *
 * @property User $item
 * @property UserList $list
 */
class UserListItem extends BaseTableRowItem
{
    use NoSubjectLink;

    protected function init()
    {
        $this->setNoSubjectLink($this->list->getNoSubjectLink());
    }

    protected function assembleVisual(BaseHtmlElement $visual)
    {
        $visual->add(new HtmlElement('div', ['class' => 'user-ball'], $this->item->display_name[0]));
    }

    protected function assembleTitle(BaseHtmlElement $title)
    {
        $title->add([
            $this->getNoSubjectLink()
                ? new HtmlElement('span', ['class' => 'subject'], $this->item->display_name)
                : new Link($this->item->display_name, Links::user($this->item), ['class' => 'subject']),
            new HtmlElement('br'),
            $this->item->name
        ]);
    }

    protected function assembleColumns(HtmlDocument $columns)
    {
        $columns->add([
            $this->createColumn($this->item->email),
            $this->createColumn($this->item->pager)
        ]);
    }
}
