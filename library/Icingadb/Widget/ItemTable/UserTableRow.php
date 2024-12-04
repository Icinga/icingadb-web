<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\User;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Web\Common\BaseTableRowItem;
use ipl\Web\Widget\Link;

/**
 * User item of a user list. Represents one database row.
 *
 * @property User $item
 * @property UserTable $table
 */
class UserTableRow extends BaseTableRowItem
{
    protected $defaultAttributes = ['class' => 'user-table-row'];

    protected function init(): void
    {
        $this->table->addDetailFilterAttribute($this, Filter::equal('name', $this->item->name));
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new HtmlElement(
            'div',
            Attributes::create(['class' => 'user-ball']),
            Text::create($this->item->display_name[0])
        ));
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(
            new Link($this->item->display_name, Links::user($this->item), ['class' => 'subject']),
            new HtmlElement('span', null, Text::create($this->item->name))
        );
    }

    protected function assembleColumns(HtmlDocument $columns): void
    {
    }
}
