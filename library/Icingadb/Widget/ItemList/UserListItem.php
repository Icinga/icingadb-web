<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\BaseTableRowItem;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Model\User;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
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
        $visual->addHtml(new HtmlElement(
            'div',
            Attributes::create(['class' => 'user-ball']),
            Text::create($this->item->display_name[0])
        ));
    }

    protected function assembleTitle(BaseHtmlElement $title)
    {
        $title->addHtml(
            $this->getNoSubjectLink()
                ? new HtmlElement(
                    'span',
                    Attributes::create(['class' => 'subject']),
                    Text::create($this->item->display_name)
                )
                : new Link($this->item->display_name, Links::user($this->item), ['class' => 'subject']),
            new HtmlElement('br'),
            Text::create($this->item->name)
        );
    }

    protected function assembleColumns(HtmlDocument $columns)
    {
        $columns->addHtml(
            $this->createColumn($this->item->email),
            $this->createColumn($this->item->pager)
        );
    }
}
