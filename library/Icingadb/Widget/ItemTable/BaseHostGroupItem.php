<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Hostgroupsummary;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Common\BaseTableRowItem;
use ipl\Web\Widget\Link;

/**
 * Hostgroup item of a hostgroup list. Represents one database row.
 *
 * @property Hostgroupsummary $item
 * @property HostgroupTable $table
 */
abstract class BaseHostGroupItem extends BaseTableRowItem
{
    use Translation;

    protected function init(): void
    {
        $this->table->addDetailFilterAttribute($this, Filter::equal('name', $this->item->name));
    }

    protected function createSubject(): BaseHtmlElement
    {
        $link = new Link(
            $this->item->display_name,
            Links::hostgroup($this->item),
            [
                'class' => 'subject',
                'title' => sprintf(
                    $this->translate('List all hosts in the group "%s"'),
                    $this->item->display_name
                )
            ]
        );
        if ($this->table->hasBaseFilter()) {
            $link->getUrl()->setFilter($this->table->getBaseFilter());
        }

        return $link;
    }

    protected function createCaption(): BaseHtmlElement
    {
        return new HtmlElement('span', null, Text::create($this->item->name));
    }
}
