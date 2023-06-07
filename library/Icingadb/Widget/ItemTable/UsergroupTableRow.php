<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\BaseTableRowItem;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Usergroup;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Link;

/**
 * Usergroup item of a usergroup list. Represents one database row.
 *
 * @property Usergroup $item
 * @property UsergroupTable $table
 */
class UsergroupTableRow extends BaseTableRowItem
{
    protected $defaultAttributes = ['class' => 'usergroup-table-row'];

    protected function init()
    {
        if (isset($this->table)) {
            $this->table->addDetailFilterAttribute($this, Filter::equal('name', $this->item->name));
        }
    }

    protected function assembleVisual(BaseHtmlElement $visual)
    {
        $visual->addHtml(new HtmlElement(
            'div',
            Attributes::create(['class' => 'usergroup-ball']),
            Text::create($this->item->display_name[0])
        ));
    }

    protected function assembleTitle(BaseHtmlElement $title)
    {
        $title->addHtml(
            isset($this->table)
                ? new Link($this->item->display_name, Links::usergroup($this->item), ['class' => 'subject'])
                : new HtmlElement(
                    'span',
                    Attributes::create(['class' => 'subject']),
                    Text::create($this->item->display_name)
                ),
            new HtmlElement('span', null, Text::create($this->item->name))
        );
    }

    protected function assembleColumns(HtmlDocument $columns)
    {
    }
}
