<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\BaseTableRowItem;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Hostgroup;
use Icinga\Module\Icingadb\Widget\Detail\HostStatistics;
use Icinga\Module\Icingadb\Widget\Detail\ServiceStatistics;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Link;

/**
 * Hostgroup table row of a hostgroup table. Represents one database row.
 *
 * @property Hostgroup $item
 * @property HostgroupTable $table
 */
class HostgroupTableRow extends BaseTableRowItem
{
    protected $defaultAttributes = ['class' => 'hostgroup-table-row'];

    protected function init()
    {
        if (isset($this->table)) {
            $this->table->addDetailFilterAttribute($this, Filter::equal('name', $this->item->name));
        }
    }

    protected function assembleColumns(HtmlDocument $columns)
    {
        $hostStats = new HostStatistics($this->item);

        $hostStats->setBaseFilter(Filter::equal('hostgroup.name', $this->item->name));
        if (isset($this->table) && $this->table->hasBaseFilter()) {
            $hostStats->setBaseFilter(
                Filter::all($hostStats->getBaseFilter(), $this->table->getBaseFilter())
            );
        }

        $columns->addHtml($this->createColumn($hostStats));

        $serviceStats = new ServiceStatistics($this->item);

        $serviceStats->setBaseFilter(Filter::equal('hostgroup.name', $this->item->name));
        if (isset($this->table) && $this->table->hasBaseFilter()) {
            $serviceStats->setBaseFilter(
                Filter::all($serviceStats->getBaseFilter(), $this->table->getBaseFilter())
            );
        }

        $columns->addHtml($this->createColumn($serviceStats));
    }

    protected function assembleTitle(BaseHtmlElement $title)
    {
        $title->addHtml(
            isset($this->table)
                ? new Link($this->item->display_name, Links::hostgroup($this->item), ['class' => 'subject'])
                : new HtmlElement(
                    'span',
                    Attributes::create(['class' => 'subject']),
                    Text::create($this->item->display_name)
                ),
            new HtmlElement('span', null, Text::create($this->item->name))
        );
    }
}
